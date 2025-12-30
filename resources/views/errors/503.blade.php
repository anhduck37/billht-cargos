<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hệ thống đang bảo trì</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .container {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 40px;
            max-width: 520px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 26px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        p {
            font-size: 15px;
            line-height: 1.6;
            color: #d1d5db;
        }

        .divider {
            height: 1px;
            background: rgba(255,255,255,0.2);
            margin: 24px 0;
        }

        .status {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.15);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .footer {
            margin-top: 20px;
            font-size: 13px;
            color: #9ca3af;
        }

        .footer strong {
            color: #fff;
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 22px;
            }
            .icon {
                font-size: 52px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="icon">🚧</div>

    <span class="status">503 – Maintenance Mode</span>

    <h1>Hệ thống đang bảo trì</h1>

    <p>
        Chúng tôi đang nâng cấp hệ thống để phục vụ bạn tốt hơn.<br>
        Vui lòng quay lại sau ít phút.
    </p>

    <div class="divider"></div>

    <p>
        Nếu bạn là <strong>nhân viên / bưu tá</strong>, vui lòng truy cập bằng link nội bộ
        hoặc liên hệ quản trị hệ thống.
    </p>

    <div class="footer">
        © <strong>HT Cargo CRM</strong> — Logistics Management System
    </div>
</div>

</body>
</html>
