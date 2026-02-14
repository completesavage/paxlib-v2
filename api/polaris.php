<?php
/**
 * Polaris API Helper
 * Handles authentication, patron lookup, item lookup, and hold placement
 */

require_once __DIR__ . '/../config.php';

class PolarisAPI {
    private $baseUrl = 'https://leap.illinoisheartland.org/Polaris.ApplicationServices';
    private $langCode = 'eng';
    private $siteId = '20';
    private $orgId = '699';
    private $workstationId = '3073';
    
    private $username;
    private $password;
    private $accessToken;
    private $accessSecret;
    private $siteDomain;
    
    public function __construct() {
        global $username, $password;
        $this->username = $username;
        $this->password = $password;
    }
    
    /**
     * Authenticate with Polaris API
     */
    public function authenticate() {
        if ($this->accessToken) return true;
        
        $authUrl = "{$this->baseUrl}/api/v1/{$this->langCode}/{$this->siteId}/authentication/staffuser";
        $basicToken = base64_encode($this->username . ':' . $this->password);
        
        $result = $this->doRequest('POST', $authUrl, [
            "Authorization: Basic $basicToken",
            "Accept: application/json",
            "Content-Length: 0"
        ], '');
        
        if (!$result['ok']) return false;
        
        $data = $result['data'];
        if (!isset($data['AccessToken'])) return false;
        
        $this->siteDomain = $data['SiteDomain'];
        $this->accessToken = $data['AccessToken'];
        $this->accessSecret = $data['AccessSecret'];
        
        return true;
    }
    
    /**
     * Make authenticated API request
     */
    public function apiRequest($method, $path, $body = null) {
        if (!$this->authenticate()) {
            return ['ok' => false, 'error' => 'Authentication failed'];
        }
        
        $url = "{$this->baseUrl}/api/v1/{$this->langCode}/{$this->siteId}/" . ltrim($path, '/');
        
        $headers = [
            "Authorization: PAS {$this->siteDomain}:{$this->accessToken}:{$this->accessSecret}",
            "Accept: application/json"
        ];
        
        if ($body !== null) {
            $headers[] = "Content-Type: application/json";
        }
        
        return $this->doRequest($method, $url, $headers, $body);
    }
    
    /**
     * Get item record by barcode
     */
    public function getItemByBarcode($barcode) {
        $path = "polaris/{$this->orgId}/{$this->workstationId}/itemrecords/{$barcode}/?isBarcode=true";
        return $this->apiRequest('GET', $path);
    }
    
    /**
     * Get patron by barcode
     */
    public function getPatronByBarcode($barcode) {
        // Step 1: Get PatronID from barcode
        $pathId = "polaris/{$this->orgId}/{$this->workstationId}/ids/patrons?id={$barcode}&type=barcode";
        $resultId = $this->apiRequest('GET', $pathId);
    
        if (!$resultId['ok'] || empty($resultId['data'])) {
            return ['ok' => false, 'error' => 'Patron not found', 'raw' => $resultId['raw']];
        }
    
        $patronId = is_array($resultId['data']) ? $resultId['data'][0] : $resultId['data'];
    
        // Step 2: Get full patron data
        $pathPatron = "polaris/{$this->orgId}/{$this->workstationId}/patrons/{$patronId}/";
        return $this->apiRequest('GET', $pathPatron);
    }
    
    /**
     * Place a local hold request with automatic handling of conversation steps
     */
    public function placeLocalHold($patronId, $bibRecordId, $pickupBranchId, $origin = 2) {
        $today = date('Y-m-d\TH:i:s');
        $future = date('Y-m-d\TH:i:s', strtotime('+6 months'));
        
        // Build base body with all required fields
        $baseBody = [
            'PatronID' => (int)$patronId,
            'PickupBranchID' => (int)$pickupBranchId,
            'Origin' => (int)$origin,
            'ActivationDate' => $today,
            'ExpirationDate' => $future,
            'BibliographicRecordID' => (int)$bibRecordId
        ];
        
        $body = array_merge($baseBody, ['ProcedureStep' => 1]);
        
        $maxAttempts = 10;
        $attempts = 0;
        
        error_log("Starting hold placement loop");
        
        while ($attempts < $maxAttempts) {
            $attempts++;
            error_log("Hold attempt $attempts with body: " . json_encode($body));
            
            $response = $this->apiRequest(
                'POST', 
                'holds?bulkmode=false&isORSStaffNoteManuallySupplied=false', 
                json_encode($body)
            );
            
            error_log("Hold response: " . json_encode($response));
            
            // Check for API-level errors
            if (!$response['ok']) {
                error_log("API request failed with status: " . ($response['status'] ?? 'unknown'));
                return [
                    'ok' => false,
                    'error' => 'API request failed',
                    'status' => $response['status'] ?? null,
                    'details' => $response
                ];
            }
            
            // Check if we have valid response data
            if (!isset($response['data']) || !is_array($response['data'])) {
                error_log("Invalid response data structure");
                return [
                    'ok' => false,
                    'error' => 'Invalid response from API',
                    'details' => $response
                ];
            }
            
            $data = $response['data'];
            
            // Check if hold was successfully placed
            if (isset($data['Success']) && $data['Success'] === true) {
                error_log("Hold placed successfully! HoldRequestID: " . ($data['HoldRequestID'] ?? 'unknown'));
                return [
                    'ok' => true,
                    'data' => $data
                ];
            }
            
            // Check if we need to continue the conversation
            if (isset($data['PAPIProcedureStep'])) {
                $step = $data['PAPIProcedureStep'];
                error_log("Procedure step $step encountered: " . ($data['Message'] ?? 'no message'));
                
                // Rebuild body for next iteration, always including all base fields
                $body = array_merge($baseBody, ['ProcedureStep' => $step]);
                
                // Handle different procedure steps
                switch ($step) {
                    case 2:  // Patron blocks - continue anyway
                    case 20: // Title not holdable - try to bypass
                    case 21: // Duplicate holds - continue anyway
                    case 22: // Max holds reached - try to bypass
                    case 32: // Material type limit - try to bypass
                        $body['Answer'] = 1; // Yes/Continue
                        error_log("Bypassing step $step with Answer=1");
                        continue 2; // Continue the while loop
                        
                    case 25: // Select designation
                    case 26: // Select volume
                        // If we have options, select the first one
                        if (isset($data['DesignationsOrVolumes']) && !empty($data['DesignationsOrVolumes'])) {
                            $body['Answer'] = 1;
                            $fieldName = ($step == 25 ? 'Designation' : 'VolumeNumber');
                            $body[$fieldName] = $data['DesignationsOrVolumes'][0];
                            error_log("Selected $fieldName: " . $body[$fieldName]);
                            continue 2;
                        }
                        break;
                        
                    case 27: // Item vs serial
                    case 28: // Promote to bib level
                        $body['Answer'] = 1; // Yes
                        error_log("Answering Yes to step $step");
                        continue 2;
                }
            }
            
            // If we get here, we couldn't handle the response automatically
            error_log("Could not automatically handle response, returning error");
            return [
                'ok' => false,
                'data' => $data,
                'message' => $data['Message'] ?? 'Unknown error'
            ];
        }
        
        // Max attempts reached
        error_log("Max attempts ($maxAttempts) reached");
        return [
            'ok' => false,
            'error' => 'Maximum conversation attempts reached',
            'details' => $response ?? null
        ];
    }
    
    /**
     * Build Syndetics cover URL
     */
    public function buildCoverUrl($upc = null, $oclc = null, $isbn = null) {
        $client = defined('SYNDETICS_CLIENT') ? SYNDETICS_CLIENT : 'ilheartland';
        $base = "https://secure.syndetics.com/index.aspx?client={$client}";
        
        if ($upc) {
            return "{$base}&upc={$upc}/MC.GIF";
        } elseif ($oclc) {
            // Clean OCLC number
            $oclc = preg_replace('/[^0-9]/', '', $oclc);
            return "{$base}&oclc={$oclc}/MC.GIF";
        } elseif ($isbn) {
            $isbn = preg_replace('/[^0-9X]/', '', strtoupper($isbn));
            return "{$base}&isbn={$isbn}/MC.GIF";
        }
        
        return null;
    }
    
    /**
     * Low-level HTTP request
     */
    private function doRequest($method, $url, $headers = [], $body = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        $responseBody = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($responseBody === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return ['ok' => false, 'error' => 'curl error: ' . $err];
        }
        
        curl_close($ch);
        
        $data = json_decode($responseBody, true);
        
        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'data' => $data,
            'raw' => $responseBody
        ];
    }
}
