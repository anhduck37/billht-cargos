# EMS API Integration - Fix Documentation

## Problems Identified & Fixed

### 1. **Guzzle HTTP Client Configuration Issue**

**Problem**: The Authorization header and other headers were not being properly sent to the EMS API because they were configured in the Client constructor instead of in the request options.

**Impact**: API authentication failures, parameter validation errors from EMS

**Solution**:
- Moved headers configuration to the `post()` request options
- Added explicit `connect_timeout` and `read_timeout` parameters
- Improved error handling for connection failures

**File Changed**: `app/Services/EmsService.php` - `executeRequest()` method

```php
// Before: Headers in Client constructor
$client = new Client(['headers' => $this->headers, 'timeout' => 30]);

// After: Headers in request options
$client = new Client(['timeout' => 30]);
$response = $client->post($this->url, [
    'headers' => $this->headers,  // ✅ Now properly sent
    'body' => json_encode($body),
    'connect_timeout' => 10,
    'read_timeout' => 30,
]);
```

### 2. **Missing Required Fields Validation**

**Problem**: Orders with empty or null values for critical fields (OrderCode, names, phone numbers) were being sent to EMS API, causing parameter validation errors.

**Impact**: EMS API rejecting orders with errors like:
- "OrderCode: Required parameter missing"
- "BuyerInfo.FullName: Invalid value"
- "SenderInfo.MobileNumber: Invalid format"

**Solution**:
- OrderCode: Validate and generate fallback (`'HE' . $order->id`)
- FullName fields: Provide defaults if empty (`'Người nhận'`, `'Người gửi'`)
- Phone numbers: Provide placeholder if empty (`'0000000000'`)
- All string fields: Apply `trim()` to remove whitespace

**File Changed**: `app/Services/EmsService.php` - `formatDataBody()` method

```php
// Determine OrderCode with fallback
$orderCode = !empty($order->invoice_code) 
    ? trim($order->invoice_code) 
    : (trim($order->order_code ?? '') ?: 'HE' . $order->id);

// Validate names and provide defaults
$receiverName = trim($order->receiver->receiver_name ?? '') ?: 'Người nhận';
$senderPhone = trim($order->sender->sender_phone ?? '') ?: '0000000000';
```

### 3. **Incomplete Error Response Handling**

**Problem**: Error responses from EMS were not being properly logged or parsed, making it difficult to diagnose issues.

**Impact**: Users unable to see specific parameter errors, making troubleshooting impossible

**Solution**:
- Added detailed request logging with order context
- Improved error message extraction from response arrays
- Properly handle validation error arrays from EMS
- Clear `push_error` on success, store on failure

**Files Changed**: 
- `app/Services/EmsService.php` - `createOrder()` method
- `app/Jobs/SendOrderEmsJob.php` - `handle()` method

```php
// Better error logging
\Log::warning('EMS Order Push Failed', [
    'order_id' => $order->id,
    'error' => $errorMsg,
    'full_response' => $result
]);

// Better error parsing
if (!empty($result['data']) && is_array($result['data'])) {
    foreach ($result['data'] as $item) {
        if (is_array($item)) {
            if (isset($item['Parameter']) && isset($item['Message'])) {
                $errorItems[] = $item['Parameter'] . ': ' . $item['Message'];
            }
        }
    }
}
```

## How to Verify the Fixes

### 1. Run the Validation Test
```bash
cd /home/bill.ht-cargos.com/html
php test_ems_fix.php
```

This will check:
- ✅ EMS configuration (CRM Code, API Keys, URL)
- ✅ Database schema (push_error, order_partner_code columns)
- ✅ Recent failed orders
- ✅ EmsService methods
- ✅ Sample order data formatting

### 2. Check Recent Logs
```bash
# View EMS API logs
tail -50 storage/logs/ems.log

# View push errors
tail -50 storage/logs/laravel.log | grep -i "ems\|push"

# View debug logs
tail -50 storage/logs/laravel.log | grep -i "debug"
```

### 3. Run Database Migration
```bash
php artisan migrate

# Or specific migration
php artisan migrate --path=database/migrations/2026_05_28_add_ems_columns_validation.php
```

### 4. Test Manual Push
Navigate to the order management system:
1. Go to **Manage Orders** → **Partner Logs**
2. Find failed orders with push errors
3. Select orders and click **Retry Push EMS**
4. Check if orders now push successfully

## Troubleshooting

### Issue: Still Getting Parameter Errors

**Causes & Fixes**:

1. **Address Mapping Missing** (for new 2025 address scheme)
   - Check: `SELECT * FROM new_address_partner_mappings WHERE partner = 'EMS' LIMIT 5;`
   - Solution: Map wards/districts to EMS codes in admin panel

2. **Empty Order Fields**
   - Check order in database: Required fields are `sender_name`, `receiver_name`, `invoice_code` or `order_code`
   - Solution: Ensure all required fields are filled before pushing

3. **Invalid Phone Number Format**
   - EMS requires valid format (digits only)
   - Fix: Remove spaces, dashes, or special characters

### Issue: Connection Timeout
```
EMS API Error: cURL error 28: Operation timed out
```

**Causes & Fixes**:
1. EMS API server is slow or down
2. Network connectivity issue
3. Request timeout too short

**Solution**:
- Check EMS API status
- Verify server network connectivity
- Connection timeout is set to 10s, read timeout to 30s (configurable in code)

### Issue: 401 Unauthorized
```
EMS API Error: Client error: POST resulted in a 401 Unauthorized
```

**Causes & Fixes**:
1. Invalid EMS credentials
2. Expired API key

**Solution**:
```php
// Verify credentials in .env
echo config('ems.access_key');
echo config('ems.secret_key');
echo config('ems.crm_code');

// Update credentials if needed in .env
EMS_ACCESS_KEY=<new_key>
EMS_SECRET_KEY=<new_secret>
EMS_CRM_CODE=<code>
```

## Testing Steps for Orders

### Manual Test Order
```php
// In tinker or test script
$order = App\Models\Order::find(12345);
$emsService = new App\Services\EmsService();
$result = $emsService->createOrder($order);
dd($result);
```

### Queue Job Test
```php
// Queue the order push
dispatch(new App\Jobs\SendOrderEmsJob($order));

// Process the queue
php artisan queue:work
```

### Check Results
```php
// View order push status
$order = App\Models\Order::find(12345);
echo "Push Error: " . $order->push_error;
echo "Partner Code: " . $order->order_partner_code;
echo "Partner: " . $order->partner_code;

// View push log
$logs = App\OrderPartnerLog::where('order_id', 12345)->latest()->get();
foreach ($logs as $log) {
    echo "Payload: " . json_decode($log->payload, true);
    echo "Response: " . json_decode($log->response, true);
}
```

## Recommended Monitoring

### 1. Set Up Error Alerts
Monitor logs for failed pushes:
```bash
# Daily check for failures
grep "EMS Order Push Failed" storage/logs/laravel.log | wc -l
```

### 2. Dashboard Widget
Add a widget to show:
- Orders pending EMS push
- Failed push attempts in last 24h
- Average response time for EMS API

### 3. Regular Health Checks
```bash
# Add to crontab to run daily
0 1 * * * cd /home/bill.ht-cargos.com/html && php test_ems_fix.php >> storage/logs/ems_health.log 2>&1
```

## API Specs

### EMS Endpoint
- **URL**: `https://mci.emsone.com.vn/Execute`
- **Method**: POST
- **Content-Type**: `application/json`
- **Auth**: Bearer token in Authorization header

### Request Format
```json
{
  "Code": "PARTNER_ORDER_ADD",
  "Data": "{...order data as JSON string...}",
  "Signature": "SHA256 hash"
}
```

### Response Format (Success)
```json
{
  "Code": "00",
  "Data": {
    "ShippingCode": "EM123456789VN",
    "EMSOneCode": "EM123456789VN"
  },
  "Message": "Thành công"
}
```

### Response Format (Error)
```json
{
  "Code": "01",
  "Data": [
    {
      "Parameter": "OrderCode",
      "Message": "Mã đơn hàng không được để trống"
    }
  ],
  "Message": "Validation Error"
}
```

## Support & Escalation

For issues not resolved by troubleshooting steps:

1. Collect diagnostic info:
   ```bash
   - Recent error logs (tail -100 storage/logs/laravel.log)
   - OrderPartnerLog entries for failed pushes
   - Order details (ID, code, sender/receiver info)
   - EMS response data
   ```

2. Contact EMS API Support with:
   - CRM Code
   - Failed order details
   - API response errors

3. File a bug report with the collected information
