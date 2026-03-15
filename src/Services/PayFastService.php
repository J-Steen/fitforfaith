<?php
namespace App\Services;

class PayFastService {
    /**
     * Build the array of PayFast POST fields for a payment redirect.
     */
    public static function buildPaymentFields(int $donationId, int $amountCents, array $user, string $itemName = ''): array {
        $amount = number_format($amountCents / 100, 2, '.', '');

        $data = [
            'merchant_id'   => PAYFAST_MERCHANT_ID,
            'merchant_key'  => PAYFAST_MERCHANT_KEY,
            'return_url'    => PAYFAST_RETURN_URL,
            'cancel_url'    => PAYFAST_CANCEL_URL,
            'notify_url'    => PAYFAST_NOTIFY_URL,
            'name_first'    => $user['first_name'],
            'name_last'     => $user['last_name'],
            'email_address' => $user['email'],
            'amount'        => $amount,
            'item_name'     => $itemName ?: 'FitForFaith Registration Fee',
            'm_payment_id'  => (string)$donationId,
        ];

        // Add passphrase if set
        if (PAYFAST_PASSPHRASE !== '') {
            $data['passphrase'] = PAYFAST_PASSPHRASE;
        }

        $data['signature'] = self::generateSignature($data);

        // Remove passphrase from form fields (it's only used in signature)
        unset($data['passphrase']);

        return $data;
    }

    /**
     * Generate PayFast MD5 signature.
     */
    public static function generateSignature(array $data): string {
        // Build query string (exclude signature itself)
        $parts = [];
        foreach ($data as $key => $val) {
            if ($key === 'signature') continue;
            $parts[] = $key . '=' . urlencode(trim((string)$val));
        }
        $queryString = implode('&', $parts);
        return md5($queryString);
    }

    /**
     * Verify a PayFast ITN (Instant Transaction Notification).
     * Returns true if valid, false otherwise.
     */
    public static function verifyITN(array $postData): bool {
        // Step 1: Check POST data not empty
        if (empty($postData)) return false;

        // Step 2: Verify signature
        $postedSignature = $postData['signature'] ?? '';
        $checkData       = $postData;
        unset($checkData['signature']);
        if (PAYFAST_PASSPHRASE !== '') {
            $checkData['passphrase'] = PAYFAST_PASSPHRASE;
        }
        if (self::generateSignature($checkData) !== $postedSignature) {
            app_log('PayFast ITN: Invalid signature', 'WARN');
            return false;
        }

        // Step 3: Validate source IP
        $remoteIp = self::getRealIp();
        if (!in_array($remoteIp, PAYFAST_VALID_IPS, true) && !PAYFAST_SANDBOX) {
            app_log("PayFast ITN: Invalid source IP $remoteIp", 'WARN');
            return false;
        }

        // Step 4: HTTP validation request back to PayFast
        $pfHost  = PAYFAST_HOST;
        $pfUrl   = "https://$pfHost/eng/query/validate";
        $pfData  = http_build_query($postData);

        $ch = curl_init($pfUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $pfData,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        @curl_close($ch);

        if (strtoupper(trim($response)) !== 'VALID') {
            app_log('PayFast ITN: Validation endpoint returned: ' . $response, 'WARN');
            return false;
        }

        return true;
    }

    /**
     * Get real client IP (handle proxies).
     */
    private static function getRealIp(): string {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip  = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return '';
    }

    /**
     * Render the PayFast payment form (auto-submits via JavaScript).
     */
    public static function renderAutoSubmitForm(int $donationId, int $amountCents, array $user): string {
        $fields = self::buildPaymentFields($donationId, $amountCents, $user);
        $action = PAYFAST_URL;

        $inputs = '';
        foreach ($fields as $name => $value) {
            $inputs .= '<input type="hidden" name="' . h($name) . '" value="' . h($value) . '">' . "\n";
        }

        return <<<HTML
<form id="payfast-form" action="{$action}" method="POST">
    {$inputs}
</form>
<script>
window.addEventListener('DOMContentLoaded', function() {
    document.getElementById('payfast-form').submit();
});
</script>
HTML;
    }
}
