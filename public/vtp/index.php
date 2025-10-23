<!DOCTYPE html>
<html>
<head>
    <title>Trạng thái đồng bộ với Viettel Post</title>
    <link rel="stylesheet" type="text/css" href="/vtp/styles.css">
    <link rel="shortcut icon" type="image/png" href="https://ht-cargos.com/wp-content/uploads/2020/07/favico-1.png"/>
</head>
<body>

    <header>
<ul class="menu cf">
    <img src="/zalo/HT.png" alt="" width="170" height="46">
  <li><a target="_blank" href="https://ht-cargos.com/">Trang chủ</a></li>
  <li>
    <a target="_blank" href="https://bill.ht-cargos.com/orders">Quản lý Bill</a>
    <!-- <ul class="submenu">
      <li><a href="">Submenu item</a></li>
      <li><a href="">Submenu item</a></li>
      <li><a href="">Submenu item</a></li>
      <li><a href="">Submenu item</a></li>
    </ul>  -->          
  </li>
  <li><a target="_blank" href="https://bill.ht-cargos.com/order/tracking">Tra cứu Bill</a></li>
  <li><a target="_blank" href="https://ht-cargos.com/lien-he/">Liên hệ</a></li>
</ul>
    </header>

    <div class="container">
       <h1>Trạng thái đơn Viettel Post, EMS</h1>

        <form method="get">
            <label for="filter_date">+ Lọc theo ngày tháng:</label>
            <input type="date" id="filter_date" name="filter_date">
            
            <label for="filter_status">| Trạng thái:</label>
            <select id="filter_status" name="filter_status">
                <option value="">Tất cả</option>
                <option value="1">Thành công</option>
                <option value="0">Thất bại</option>
            </select>
            
            <button type="submit">Xem kết quả</button>
        </form>

        
 <?php
        // Thay đổi các thông tin kết nối dựa trên cấu hình của bạn
        $servername = "localhost";
        $username = "billhtcargoscom_NDZkYzU1ODA2YTI0OG_username";
        $password = "NDZkYzU1ODA2YTI0OGRlOGFiZWU0YWE4ZjE1";
        $dbname = "billhtcargoscom_NDZkYzU1ODA2YTI0OG_dbname";

        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            die("Kết nối thất bại: " . $conn->connect_error);
        }

        // Lấy ngày hiện tại
        $currentDate = date('Y-m-d');

        // Lấy tháng hiện tại
        $currentMonth = date('Y-m');

        // Đếm số lượng trạng thái thành công trong tháng hiện tại
        $sql_success_count = "SELECT COUNT(*) AS success_count FROM order_partner_logs WHERE status = 1 AND DATE_FORMAT(updated_at, '%Y-%m') = '$currentMonth'";
        $result_success_count = $conn->query($sql_success_count);
        $row_success_count = $result_success_count->fetch_assoc();
        $success_count = $row_success_count['success_count'];

        // Đếm số lượng thất bại trong tháng hiện tại
        $sql_error_count = "SELECT COUNT(*) AS error_count FROM order_partner_logs WHERE status = 0 AND DATE_FORMAT(updated_at, '%Y-%m') = '$currentMonth'";
        $result_error_count = $conn->query($sql_error_count);
        $row_error_count = $result_error_count->fetch_assoc();
        $error_count = $row_error_count['error_count'];

        echo "<p>+ Số đơn qua Viettel Post thành công trong tháng: <b>$success_count</b> | Số đơn thất bại trong tháng: <b>$error_count</b></p>";


        $items_per_page = 50;
        $current_page = isset($_GET['page']) ? $_GET['page'] : 1;
        $offset = ($current_page - 1) * $items_per_page;

        $filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';

        $filter_sql = "";
        if (!empty($filter_date)) {
            $filter_sql = " AND DATE(updated_at) = '$filter_date'";
        }

        // Xử lý lọc theo trạng thái
        $filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';

        $status_sql = "";
        if ($filter_status !== '') {
            if ($filter_status == 1) {
                $status_sql = " AND status = 1"; // Thành công
            } elseif ($filter_status == 0) {
                $status_sql = " AND status = 0"; // Thất bại
            }
        }

        // Xử lý lọc theo ngày tháng
        $filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';

        $filter_sql = "";
        if (!empty($filter_date)) {
            $filter_sql = " AND DATE(updated_at) = '$filter_date'";
        }

 $sql = "SELECT * FROM order_partner_logs WHERE 1 $status_sql $filter_sql ORDER BY updated_at DESC LIMIT $offset, $items_per_page";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
echo "<table>";
echo "<tr><th>Mã vận đơn</th><th>Tên người gửi</th><th>Trạng thái</th><th>Ngày Cập Nhật</th></tr>";
while ($row = $result->fetch_assoc()) {
    $payload = json_decode($row["payload"], true);
    
    // Lấy order number từ cả hai trường hợp có thể
    $order_number = isset($payload["ORDER_NUMBER"]) ? $payload["ORDER_NUMBER"] : 
                   (isset($payload["order_code"]) ? $payload["order_code"] : null);
    
    // Lấy sender name từ cả hai trường hợp có thể
    $sender_name = isset($payload["SENDER_FULLNAME"]) ? $payload["SENDER_FULLNAME"] : 
                  (isset($payload["from_name"]) ? $payload["from_name"] : null);
    
    $order_id = $row["order_id"];

    $truncated_name = strlen($row["response"]) > 85 ? substr($row["response"], 0, 85) . "..." : $row["response"];
    $status_text = ($row["status"] == 1) ? "Thành công" : "Thất bại";
    if (strpos($row["response"], "[SENDER_ADDRESS]") !== false) {
        $response_text = "<b style='color: red;'>Sai hoặc thiếu địa chỉ người gửi - VTP</b>";
    } elseif (strpos($row["response"], "ORDER_NUMBER") !== false) {
        $response_text = "Thành công - VTP";            
    } elseif (strpos($row["response"], "[RECEIVER_PHONE]") !== false) {
        $response_text = "<b style='color: red;'>Sai hoặc thiếu số điện thoại người nhận - VTP</b>";
    } elseif (strpos($row["response"], "[SENDER_PHONE]") !== false) {
        $response_text = "<b>Sai hoặc thiếu số điện thoại người gửi - VTP</b>";
    } elseif (strpos($row["response"], "[RECEIVER_ADDRESS]") !== false) {
        $response_text = "Sai hoặc thiếu địa chỉ người nhận - VTP";
    } elseif (strpos($row["response"], "Incorrect data: ORDER_SERVICE") !== false) {
        $response_text = "<b>Thiếu trọng lượng - VTP</b>";
    } elseif (strpos($row["response"], "Price does not apply to this itinerary") !== false) {
        $response_text = "<b>Dịch vụ VTK không áp dụng cho gửi nội tỉnh - VTP</b>";
    } elseif (strpos($row["response"], "success") !== false) {
        $response_text = "Thành Công - EMS";
    } elseif (strpos($row["response"], "026") !== false) {
        $response_text = "Tỉnh/Thành phố người nhận không hợp lệ!";
    } elseif (strpos($row["response"], "011") !== false) {
        $response_text = "<b style='color: red;'>EMS - SĐT hoặc địa chỉ người gửi không được để trống</b>";                           
    } else {
        $response_text = $truncated_name;
    }
    
    // Tạo liên kết cho cột Mã vận đơn
    $order_link = "<a href='https://bill.ht-cargos.com/orders/{$order_id}/edit' target='_blank'>{$order_number}</a>";
    
    echo "<tr><td>" . $order_link . "</td><td>" . $sender_name . "</td><td>" . $response_text . "</td><td>" . $row["updated_at"] . "</td></tr>";
}
echo "</table>";
} else {
    echo "Không có dữ liệu trong bảng.";
}

$sql_total = "SELECT COUNT(*) AS total FROM order_partner_logs WHERE 1 $filter_sql $status_sql";
$result_total = $conn->query($sql_total);
$row_total = $result_total->fetch_assoc();
$total_items = $row_total['total'];
$total_pages = ceil($total_items / $items_per_page);

$conn->close();
?>

<div class="pagination">
    <?php
    $page_range = 3; // Số trang gần trang hiện tại bạn muốn hiển thị
    $start_page = max($current_page - $page_range, 1);
    $end_page = min($current_page + $page_range, $total_pages);

    if ($start_page > 1) {
        echo "<a href='index.php?page=1&filter_date=$filter_date&filter_status=$filter_status'>1</a>";
        if ($start_page > 2) {
            echo "<span class='pagination-ellipsis'>&hellip;</span>";
        }
    }

    for ($i = $start_page; $i <= $end_page; $i++) {
        $active_class = ($i == $current_page) ? "active" : "";
        echo "<a href='index.php?page=$i&filter_date=$filter_date&filter_status=$filter_status' class='$active_class'>$i</a>";
    }

    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            echo "<span class='pagination-ellipsis'>&hellip;</span>";
        }
        echo "<a href='index.php?page=$total_pages&filter_date=$filter_date&filter_status=$filter_status'>$total_pages</a>";
    }

    $next_page = $current_page + 1;
    if ($next_page <= $total_pages) {
        echo "<a href='index.php?page=$next_page&filter_date=$filter_date&filter_status=$filter_status' class='pagination-link'>Next Page</a>";
    }
    ?>
</div>

    </div>

    <footer>
        <center>
            <img src="/zalo/HT.png" alt="" width="170" height="46">
        
            <p><strong>CÔNG TY CPTM VÀ DVVC HH BẰNG ĐƯỜNG HK HTEXPRESS</strong></p>
            <p>Giấy chứng nhận Đăng ký kinh doanh số: <strong>0102290805</strong><br>    
            do Phòng ĐKKD Thành phố Hà Nội cấp ngày: 13/06/2007</p>
            <p><strong>VP giao dịch:</strong><br>
            <strong>Hà Nội:</strong> Số 27, ngõ 71 Hoàng Văn Thái, Khương Trung, Thanh Xuân, Hà Nội.<br>
            <strong>HCM:</strong> A51A Bạch Đằng, Phường 2, Q. Tân Bình, HCM
            </p>    
             
        </center>
        
    </footer>

</body>
</html>
