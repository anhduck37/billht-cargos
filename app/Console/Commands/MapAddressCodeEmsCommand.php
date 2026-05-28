<?php

namespace App\Console\Commands;

use App\City;
use App\District;
use App\Services\EmsService;
use App\Ward;
use Illuminate\Console\Command;

use function PHPUnit\Framework\isEmpty;

class MapAddressCodeEmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'address_code_ems';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Bắt đầu đồng bộ danh mục phường xã EMS...");
        
        // 1. Download CSV từ link google sheet
        $csvUrl = 'https://docs.google.com/spreadsheets/d/1aGQxzkpvNSzc4gFuheaskNcvcTFoPKdS/export?format=csv&gid=1383598002';
        $csvData = file_get_contents($csvUrl);
        if (empty($csvData)) {
            $this->error("Không thể tải file CSV!");
            return;
        }

        $rows = explode("\n", $csvData);
        $emsData = [];
        
        // 2. Phân tích CSV
        foreach ($rows as $index => $row) {
            if ($index < 4) continue; // Skip header
            
            $cols = str_getcsv($row);
            if (count($cols) < 20) continue;
            
            // EMS API accepts the BCQG code set, not the MBC code set.
            // CSV columns: 11/14/17 = MBC, 12/15/18 = BCQG.
            $provCode = $cols[12];
            $provName = $cols[13];
            $distCode = $cols[15];
            $distName = $cols[16];
            $wardCode = $cols[18];
            $wardName = $cols[19];
            
            if (empty($provCode) || empty($distCode) || empty($wardCode)) continue;
            
            $key = $this->normalizeName($provName) . '|' . $this->normalizeName($distName) . '|' . $this->normalizeName($wardName);
            
            $pKey = $this->resolveAlias($this->normalizeName($provName));
            $dKey = $this->resolveAlias($this->normalizeName($distName));
            $wKey = $this->resolveAlias($this->normalizeName($wardName));
            
            if (!isset($emsData[$pKey])) $emsData[$pKey] = [];
            if (!isset($emsData[$pKey][$dKey])) $emsData[$pKey][$dKey] = [];
            
            $emsData[$pKey][$dKey][$wKey] = [
                'p_id' => trim($provCode),
                'd_id' => trim($distCode),
                'w_id' => trim($wardCode)
            ];
        }
        
        // Count total nodes loaded for verification
        $totalEms = 0;
        foreach($emsData as $pv) {
            foreach($pv as $dv) {
                $totalEms += count($dv);
            }
        }
        $this->info("Đã tải " . $totalEms . " bản ghi EMS (có group).");
        \File::put(storage_path('logs/ems_data_dump.json'), json_encode($emsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 3. Quét hệ thống DB và Map
        $cities = \App\City::all()->keyBy('id');
        $districts = \App\District::all()->keyBy('id');
        $wards = Ward::all();
        
        $unmappedCount = 0;
        $unmappedList = [];
        
        $this->info("Bắt đầu map " . count($wards) . " phường/xã nội bộ...");
        
        foreach ($wards as $ward) {
            $district = $districts->get($ward->district_id);
            if (!$district) continue;
            
            $city = $cities->get($district->city_id);
            if (!$city) continue;
            
            $pName = $this->normalizeName($city->city_name);
            $dName = $this->normalizeName($district->district_name);
            $wName = $this->normalizeName($ward->ward_name);
            
            // Xử lý alias 
            $pName = $this->resolveAlias($pName);
            $dName = $this->resolveAlias($dName);
            $wName = $this->resolveAlias($wName);
            
            $matchedData = null;
            
            // Tìm District Ems
            $districtEms = null;
            if (isset($emsData[$pName][$dName])) {
                $districtEms = $emsData[$pName][$dName];
            } else if (isset($emsData[$pName])) {
                // 2. Fuzzy match district
                foreach ($emsData[$pName] as $emsDkey => $emsDval) {
                    $d1 = (string)$dName;
                    $d2 = (string)$emsDkey;
                    if ($d1 === '' || $d2 === '') continue;
                    
                    if (strpos($d1, $d2) !== false || strpos($d2, $d1) !== false) {
                        $districtEms = $emsDval;
                        break;
                    }
                }
            }
            
            if ($districtEms) {
                // 1. Exact match ward
                if (isset($districtEms[$wName])) {
                    $matchedData = $districtEms[$wName];
                } else {
                    // 2. Fuzzy match ward
                    foreach ($districtEms as $emsWkey => $emsVal) {
                        $w1 = (string)$wName;
                        $w2 = (string)$emsWkey;
                        if ($w1 === '' || $w2 === '') continue;
                        
                        if (strpos($w1, $w2) !== false || strpos($w2, $w1) !== false) {
                            $matchedData = $emsVal;
                            break;
                        }
                    }
                }
            }
            
            if ($matchedData !== null) {
                // Update ward
                if ($ward->ems_code != $matchedData['w_id']) {
                    $ward->ems_code = $matchedData['w_id'];
                    $ward->save();
                }
                
                // Update district
                if ($district->ems_code != $matchedData['d_id']) {
                    $district->ems_code = $matchedData['d_id'];
                    $district->save();
                }
                
                // Update province
                if ($city->ems_code != $matchedData['p_id']) {
                    $city->ems_code = $matchedData['p_id'];
                    $city->save();
                }
            } else {
                $unmappedCount++;
                $unmappedList[] = "ID: {$ward->id} - {$city->city_name} - {$district->district_name} - {$ward->ward_name} (Look: {$pName}|{$dName}|{$wName})";
            }
        }
        
        $this->info("Hoàn tất! Cập nhật thành công. Lỗi không map được: $unmappedCount");
        if ($unmappedCount > 0) {
            \File::put(storage_path('logs/ems_unmapped_wards.json'), json_encode($unmappedList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("Danh sách phường xã chưa map được lưu tại: storage/logs/ems_unmapped_wards.json");
        }
    }

    private function resolveAlias($name)
    {
        // Replace directly common patterns within the name string
        $replacements = [
            'BRVT' => 'BARIAVUNGTAU',
            'TTHUE' => 'THUATHIENHUE',
            'THUATHIENHUE' => 'TTHUE',
            'DAC LAC' => 'LAM DONG', // As requested previously
            'HCM' => 'HOCHIMINH',
            'TPHCM' => 'HOCHIMINH',
            
            // Numbers
            'MOT' => '1', 'HAI' => '2', 'BA' => '3', 'BON' => '4', 'TU' => '4',
            'NAM' => '5', 'SAU' => '6', 'BAY' => '7', 'TAM' => '8', 'CHIN' => '9',
            'MUOI' => '10', 'MUOIMOT' => '11', 'MUOIHAI' => '12', 'MUOIBA' => '13',
            'MUOIBON' => '14', 'MUOILAM' => '15', 'MUOISAU' => '16'
        ];

        // Ensure string is replaced
        if (isset($replacements[$name])) {
            return $replacements[$name];
        }

        return $name;
    }

    private function normalizeName($str)
    {
        // Fix weird "Ð" U+00D0 character commonly used instead of "Đ" U+0110 in Vietnamese typography
        $str = str_replace('Ð', 'D', $str);
        
        $emsService = new EmsService();
        $str = strtoupper($emsService->stripVN($str));
        
        // Cần loại bỏ cả từ viết tắt lẫn từ đầy đủ (sau khi đã stripVN ra không dấu)
        $prefixes = [
            'THANH PHO ', 'TINH ', 'QUAN ', 'HUYEN DAO ', 'HUYEN ', 'THI XA ', 'PHUONG ', 'XA ', 'THI TRAN ', 'DAO ',
            'TT.', 'TT ', 'TP.', 'T.', 'Q.', 'H.', 'TX.', 'P.', 'X.'
        ];
        
        // Loop multiple times to handle cases like "HUYEN DAO" where multiple terms might exist or nested.
        foreach ($prefixes as $p) {
            if (strpos($str, $p) === 0) {
                // Remove the prefix from the beginning
                $str = substr($str, strlen($p));
            }
        }
        
        $str = str_replace(['-', '.', ','], '', $str);
        $str = preg_replace('/\s+/', '', trim($str)); // xóa hết khoảng trắng
        
        // Remove leading zeros from trailing numbers (e.g. QUAN 06 -> QUAN 6)
        $str = preg_replace_callback('/(\D+)0+(\d+)$/', function ($matches) {
            return $matches[1] . $matches[2];
        }, $str);
        
        // Hoặc các số đứng 1 mình như 06 -> 6
        if (is_numeric($str)) {
            $str = (int)$str . "";
        }
        
        return $str;
    }
}
