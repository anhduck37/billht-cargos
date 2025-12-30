<!DOCTYPE html>
<html>
<head>
    <title>Trạng thái gửi tin zalo HT Express</title>
    <link rel="stylesheet" type="text/css" href="/zalo/styles.css">
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
       <h1>Trạng thái gửi tin nhắn zalo HTExpress</h1>

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
        $username = "wepkbmc_billht";
        $password = "NDZkYzU1ODA2YTI0OGRlOGFiZWU0YWE4ZjE1";
        $dbname = "wepkbmc_billht";

        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            die("Kết nối thất bại: " . $conn->connect_error);
        }

        // Lấy ngày hiện tại
        $currentDate = date('Y-m-d');

        // Lấy tháng hiện tại
        $currentMonth = date('Y-m');

        // Đếm số lượng trạng thái gửi tin thành công trong tháng hiện tại
        $sql_success_count = "SELECT COUNT(*) AS success_count FROM order_logs WHERE status = 1 AND DATE_FORMAT(updated_at, '%Y-%m') = '$currentMonth'";
        $result_success_count = $conn->query($sql_success_count);
        $row_success_count = $result_success_count->fetch_assoc();
        $success_count = $row_success_count['success_count'];

        // Đếm số lượng trạng thái gửi tin thất bại trong tháng hiện tại
        $sql_error_count = "SELECT COUNT(*) AS error_count FROM order_logs WHERE status = 0 AND DATE_FORMAT(updated_at, '%Y-%m') = '$currentMonth'";
        $result_error_count = $conn->query($sql_error_count);
        $row_error_count = $result_error_count->fetch_assoc();
        $error_count = $row_error_count['error_count'];

        echo "<p>+ Tin nhắn thành công trong tháng hiện tại: <b>$success_count</b> | Tin nhắn thất bại trong tháng hiện tại: <b>$error_count</b></p>";


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

 $sql = "SELECT * FROM order_logs WHERE 1 $status_sql $filter_sql ORDER BY updated_at DESC LIMIT $offset, $items_per_page";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Mã vận đơn</th><th>Ngày Cập Nhật</th><th>Trạng thái</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $truncated_name = strlen($row["response"]) > 85 ? substr($row["response"], 0, 85) . "..." : $row["response"];
        $status_text = ($row["status"] == 1) ? "Thành công" : "Thất bại";

        if (strpos($row["response"], "template data customer_name is break max length") !== false) {
            $response_text = "<b>Tên người nhận quá dài</b>";
        } elseif (strpos($row["response"], "Zalo account not existed") !== false) {
            $response_text = "<b>Số điện thoại người nhận chưa đăng ký ZALO</b>";            
        } elseif (strpos($row["response"], "ZNS daily quota exceeded") !== false) {
            $response_text = "<i>Vượt giới hạn gửi ZNS trong ngày</i>";
        } elseif (strpos($row["response"], "Phone number invalid") !== false) {
            $response_text = "<b>Số điện thoại người nhận không đúng</b>";
        } elseif (strpos($row["response"], "OA does not have permission to use this feature") !== false) {
            $response_text = "Gói ZALO OA đã hết hạn, vui lòng gia hạn để gửi tin nhắn";
        }  elseif (strpos($row["response"], "Success") !== false) {
            $response_text = "Thành công";       
        } elseif (strpos($row["response"], "Zalo version unsupported") !== false) {
            $response_text = "<b>Phiên bản ZALO người nhận không hỗ trợ</b>";
        } else {
            $response_text = $truncated_name;
        }

        echo "<tr><td>" . $row["order_code"] . "</td><td>" . $row["updated_at"] . "</td><td>" . $response_text . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "Không có dữ liệu trong bảng.";
}

$sql_total = "SELECT COUNT(*) AS total FROM order_logs WHERE 1 $filter_sql $status_sql";
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
