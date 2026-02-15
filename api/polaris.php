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
    
    private static $instance = null;
    
    public function __construct() {
        global $username, $password;
        $this->username = $username;
        $this->password = $password;
        
        error_log("PolarisAPI __construct: username=" . ($this->username ? 'SET' : 'EMPTY') . ", password=" . ($this->password ? 'SET' : 'EMPTY'));
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            error_log("PolarisAPI: Creating new singleton instance");
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Authenticate with Polaris API
     */
    public function authenticate() {
        if ($this->accessToken) return true;
        
        // Debug logging
        error_log("PolarisAPI: Attempting authentication");
        error_log("PolarisAPI: Username set: " . (empty($this->username) ? 'NO' : 'YES'));
        error_log("PolarisAPI: Password set: " . (empty($this->password) ? 'NO' : 'YES'));
        
        if (empty($this->username) || empty($this->password)) {
            error_log("PolarisAPI: Missing credentials!");
            return false;
        }
        
        $authUrl = "{$this->baseUrl}/api/v1/{$this->langCode}/{$this->siteId}/authentication/staffuser";
        $basicToken = base64_encode($this->username . ':' . $this->password);
        
        error_log("PolarisAPI: Auth URL: $authUrl");
        
        $result = $this->doRequest('POST', $authUrl, [
            "Authorization: Basic $basicToken",
            "Accept: application/json",
            "Content-Length: 0"
        ], '');
        
        error_log("PolarisAPI: Auth result ok: " . ($result['ok'] ? 'true' : 'false'));
        if (!$result['ok']) {
            error_log("PolarisAPI: Auth failed: " . ($result['error'] ?? 'unknown'));
            return false;
        }
        
        $data = $result['data'];
        if (!isset($data['AccessToken'])) {
            error_log("PolarisAPI: No AccessToken in response");
            return false;
        }
        
        $this->siteDomain = $data['SiteDomain'];
        $this->accessToken = $data['AccessToken'];
        $this->accessSecret = $data['AccessSecret'];
        
        error_log("PolarisAPI: Authentication successful!");
        
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
     * Get bibliographic record availability by ID
     * Returns availability across all branches
     */
    public function getBibAvailability($bibRecordId) {
        $path = "polaris/{$this->orgId}/{$this->workstationId}/bibliographicrecords/{$bibRecordId}/availability?nofilter=true";
        
        error_log("getBibAvailability: Checking bib ID $bibRecordId");
        
        $result = $this->apiRequest('GET', $path);
        
        error_log("getBibAvailability: API result: " . print_r($result, true));
        
        if ($result['ok'] && isset($result['data']['Details'])) {
            // Sum all branches
            $totalAvailable = 0;
            $totalCount = 0;
            
            foreach ($result['data']['Details'] as $branch) {
                $totalAvailable += $branch['AvailableCount'];
                $totalCount += $branch['TotalCount'];
            }
            
            return [
                'ok' => true,
                'available' => $totalAvailable > 0,
                'availableCount' => $totalAvailable,
                'totalCount' => $totalCount,
                'status' => $totalAvailable > 0 ? 'Available' : 'Checked Out'
            ];
        }
        
        return [
            'ok' => false,
            'error' => $result['error'] ?? 'No details in response'
        ];
    }
    
    /**
     * Bulk check availability using bibliographic record API
     * Much more efficient - checks entire bib record availability at once
     */
    public function bulkItemAvailability($movies, $batchSize = 50) {
        $startTime = microtime(true);
        $results = [];
        
        // Group movies by their bibRecordId
        $bibGroups = [];
        foreach ($movies as $movie) {
            if (isset($movie['bibRecordId']) && $movie['bibRecordId']) {
                $bibId = $movie['bibRecordId'];
                if (!isset($bibGroups[$bibId])) {
                    $bibGroups[$bibId] = [];
                }
                $bibGroups[$bibId][] = $movie['barcode'];
            } else {
                error_log("Movie missing bibRecordId: " . ($movie['barcode'] ?? 'unknown'));
            }
        }
        
        $uniqueBibCount = count($bibGroups);
        error_log("Checking availability for $uniqueBibCount unique bib records (covering " . count($movies) . " items)");
        
        $apiCallCount = 0;
        $bibIds = array_keys($bibGroups);
        
        foreach ($bibIds as $bibId) {
            $apiCallCount++;
            $callStart = microtime(true);
            
            // Get availability for this bib record
            $path = "polaris/{$this->orgId}/{$this->workstationId}/bibliographicrecords/{$bibId}/availability?nofilter=true";
            $result = $this->apiRequest('GET', $path);
            
            $callTime = round((microtime(true) - $callStart) * 1000);
            error_log("API call $apiCallCount/$uniqueBibCount for bib $bibId took {$callTime}ms");
            
            if ($result['ok'] && isset($result['data']['Details'])) {
                // Sum up ALL branches
                $totalAvailable = 0;
                $totalCount = 0;
                
                foreach ($result['data']['Details'] as $branch) {
                    $totalAvailable += $branch['AvailableCount'];
                    $totalCount += $branch['TotalCount'];
                }
                
                // Apply availability to all items in this bib group
                foreach ($bibGroups[$bibId] as $barcode) {
                    $results[$barcode] = [
                        'available' => $totalAvailable > 0,
                        'availableCount' => $totalAvailable,
                        'totalCount' => $totalCount,
                        'status' => $totalAvailable > 0 ? 'Available' : 'Checked Out'
                    ];
                }
            } else {
                error_log("Failed to get bib availability for $bibId");
                // If we can't get bib availability, mark as unknown
                foreach ($bibGroups[$bibId] as $barcode) {
                    $results[$barcode] = [
                        'available' => false,
                        'status' => 'Unknown',
                        'error' => $result['error'] ?? 'Not found'
                    ];
                }
            }
        }
        
        $totalTime = round((microtime(true) - $startTime), 2);
        error_log("Completed $apiCallCount API calls in {$totalTime}s (avg " . round($totalTime/$apiCallCount, 2) . "s per call)");
        
        return [
            'ok' => true,
            'data' => $results
        ];
    }
    
    /**
     * Legacy method - kept for backwards compatibility
     * Use bulkItemAvailability instead for better performance
     */
    public function bulkItemStatus($barcodes, $batchSize = 50) {
        $results = [];
        $chunks = array_chunk($barcodes, $batchSize);
        
        error_log("Checking status for " . count($barcodes) . " items in " . count($chunks) . " batches");
        
        foreach ($chunks as $chunkIndex => $chunk) {
            error_log("Processing batch " . ($chunkIndex + 1) . "/" . count($chunks));
            
            foreach ($chunk as $barcode) {
                // Get item status
                $result = $this->getItemByBarcode($barcode);
                
                if ($result['ok'] && isset($result['data'])) {
                    $item = $result['data'];
                    $status = $item['CirculationStatusDescription'] ?? 'Unknown';
                    $isAvailable = $this->isStatusAvailable($status);
                    
                    $results[$barcode] = [
                        'status' => $status,
                        'available' => $isAvailable,
                        'statusId' => $item['CirculationStatusID'] ?? null
                    ];
                } else {
                    $results[$barcode] = [
                        'status' => 'Unknown',
                        'available' => false,
                        'statusId' => null,
                        'error' => $result['error'] ?? 'Not found'
                    ];
                }
            }
            
            // Small delay between batches to avoid overwhelming the API
            if ($chunkIndex < count($chunks) - 1) {
                usleep(100000); // 100ms delay
            }
        }
        
        return [
            'ok' => true,
            'data' => $results
        ];
    }
    
    /**
     * Determine if a circulation status means the item is available
     */
    private function isStatusAvailable($status) {
        $status = strtolower($status);
        
        // Available statuses
        if (strpos($status, 'in') !== false && 
            strpos($status, 'transit') === false && 
            strpos($status, 'hold') === false &&
            strpos($status, 'processing') === false) {
            return true;
        }
        
        return false;
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
     * Get all holds for a patron
     * Returns holds grouped by status
     */
    public function getPatronHolds($patronId) {
        // Get holds in different statuses that are relevant to patrons
        $statuses = [
            3 => 'Active',      // Active in system
            4 => 'Pending',     // Waiting to be filled
            5 => 'Shipped',     // In transit
            6 => 'Held',        // Ready for pickup
            8 => 'Unclaimed',   // Was ready but not picked up
            17 => 'Out'         // Checked out to patron
        ];
        
        $allHolds = [];
        
        foreach ($statuses as $statusId => $statusName) {
            // branchTypeId 1 = Requesting Patron Branch
            $path = "polaris/{$this->orgId}/{$this->workstationId}/holdssummary/{$this->orgId}/1/{$statusId}";
            $result = $this->apiRequest('GET', $path);
            
            if ($result['ok'] && isset($result['data']) && is_array($result['data'])) {
                // Filter to only this patron's holds
                foreach ($result['data'] as $hold) {
                    if (isset($hold['PatronID']) && $hold['PatronID'] == $patronId) {
                        $hold['StatusName'] = $statusName;
                        $allHolds[] = $hold;
                    }
                }
            }
        }
        
        return [
            'ok' => true,
            'data' => $allHolds
        ];
    }
    
    /**
     * Cancel a hold request
     */
    public function cancelHold($holdRequestId, $patronId = null) {
        $path = "polaris/{$this->orgId}/{$this->workstationId}/holds/{$holdRequestId}?action=cancel&isstatuslistlimited=true";
        
        // Add patronId as query param if provided to verify ownership
        if ($patronId !== null) {
            $path .= "&patronId={$patronId}";
        }
        
        error_log("Cancelling hold: $holdRequestId, path: $path");
        
        // Send empty JSON object as body to satisfy Content-Length requirement
        $result = $this->apiRequest('PUT', $path, '{}');
        
        error_log("Cancel hold API result: " . print_r($result, true));
        
        if ($result['ok']) {
            return [
                'ok' => true,
                'data' => $result['data']
            ];
        } else {
            return [
                'ok' => false,
                'error' => 'Failed to cancel hold',
                'details' => $result
            ];
        }
    }
    
    /**
     * Get patron's current fines/fees
     * Returns total amount owed
     */
    public function getPatronFines($patronId) {
        $path = "polaris/{$this->orgId}/{$this->workstationId}/patrons/{$patronId}/account/outstanding";
        $result = $this->apiRequest('GET', $path);
        
        if ($result['ok'] && isset($result['data'])) {
            $data = $result['data'];
            
            // Calculate total outstanding
            $totalOwed = 0;
            if (isset($data['ChargeBalance'])) {
                $totalOwed += floatval($data['ChargeBalance']);
            }
            if (isset($data['DepositBalance'])) {
                $totalOwed += floatval($data['DepositBalance']);
            }
            
            return [
                'ok' => true,
                'totalOwed' => $totalOwed,
                'chargeBalance' => floatval($data['ChargeBalance'] ?? 0),
                'depositBalance' => floatval($data['DepositBalance'] ?? 0),
                'hasOutstanding' => $totalOwed > 0,
                'canCheckout' => $totalOwed <= 5.00  // $5 limit
            ];
        }
        
        return [
            'ok' => false,
            'error' => 'Failed to get patron fines',
            'details' => $result
        ];
    }
    
    /**
     * Get patron's current checkouts
     * Returns count and list of checked out items
     */
    public function getPatronCheckouts($patronId) {
        $path = "polaris/{$this->orgId}/{$this->workstationId}/patrons/{$patronId}/itemsout/all";
        $result = $this->apiRequest('GET', $path);
        
        if ($result['ok'] && isset($result['data']['PatronItemsOutGetRows'])) {
            $items = $result['data']['PatronItemsOutGetRows'];
            
            // Count DVD checkouts
            $dvdCount = 0;
            foreach ($items as $item) {
                $materialType = strtolower($item['FormatDescription'] ?? '');
                if (strpos($materialType, 'dvd') !== false) {
                    $dvdCount++;
                }
            }
            
            return [
                'ok' => true,
                'totalCheckouts' => count($items),
                'dvdCheckouts' => $dvdCount,
                'canCheckoutDVD' => $dvdCount < 5,  // 5 DVD limit
                'items' => $items
            ];
        }
        
        return [
            'ok' => false,
            'error' => 'Failed to get patron checkouts',
            'details' => $result
        ];
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
            
            $path = "polaris/{$this->orgId}/{$this->workstationId}/holds?bulkmode=false&isORSStaffNoteManuallySupplied=false";
            
            $response = $this->apiRequest(
                'POST', 
                $path, 
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
        
        // Always set body for PUT/POST, even if empty
        if ($method === 'PUT' || $method === 'POST') {
            if ($body === null) {
                $body = '';
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        } elseif ($body !== null) {
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
