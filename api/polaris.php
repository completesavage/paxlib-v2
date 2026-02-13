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
     * Place a hold request using workflow API
     */
    public function placeHold($patronBarcode, $bibRecordId, $pickupBranchId = null) {
        if (!$pickupBranchId) $pickupBranchId = $this->orgId;
        
        // First get patron ID
        $patronResult = $this->getPatronByBarcode($patronBarcode);
        if (!$patronResult['ok'] || !isset($patronResult['data']['PatronID'])) {
            return ['ok' => false, 'error' => 'Patron not found'];
        }
        $patronId = $patronResult['data']['PatronID'];
        
        // Use workflow API to place hold
        $workflowData = [
            'WorkflowRequestType' => 5, // PlaceHoldRequest
            'TxnBranchID' => (int)$this->orgId,
            'TxnUserID' => 1,
            'TxnWorkstationID' => (int)$this->workstationId,
            'RequestExtension' => [
                'WorkflowRequestExtensionType' => 9, // HoldRequestData
                'Data' => [
                    'PatronID' => $patronId,
                    'BibliographicRecordID' => (int)$bibRecordId,
                    'PickupBranchID' => (int)$pickupBranchId,
                    'Origin' => 2, // Staff
                    'ItemLevelHold' => false
                ]
            ],
            'WorkflowReplies' => null
        ];
        
        $path = "polaris/{$this->orgId}/{$this->workstationId}/workflow";
        $result = $this->apiRequest('POST', $path, json_encode($workflowData));
        
        // Handle workflow prompts if needed
        if ($result['ok'] && isset($result['data']['WorkflowStatus'])) {
            $status = $result['data']['WorkflowStatus'];
            
            // -3 = InputRequired, need to reply
            if ($status == -3 && isset($result['data']['WorkflowRequestGuid'])) {
                // Auto-continue for most prompts
                $guid = $result['data']['WorkflowRequestGuid'];
                $promptId = $result['data']['Prompt']['WorkflowPromptID'] ?? 0;
                
                $replyData = [
                    'WorkflowPromptID' => $promptId,
                    'WorkflowPromptResult' => 5, // Continue
                    'ReplyValue' => null,
                    'ReplyExtension' => null
                ];
                
                $replyPath = "polaris/{$this->orgId}/{$this->workstationId}/workflow/{$guid}";
                $result = $this->apiRequest('PUT', $replyPath, json_encode($replyData));
            }
            
            // 1 = CompletedSuccessfully
            if (isset($result['data']['WorkflowStatus']) && $result['data']['WorkflowStatus'] == 1) {
                return ['ok' => true, 'data' => $result['data']];
            }
        }
        
        return $result;
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
