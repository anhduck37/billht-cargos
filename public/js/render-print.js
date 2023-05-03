function renderHtml (data) {
    let html =`
    <!DOCTYPE html>
    <html lang="${data.locale}">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <meta name="csrf-token" content="${data.csrf_token}">

        <title>HTEXPRESS - Hệ thống quản lý vận đơn</title>
        <!-- Favicon -->
        <link href="${data.favicon}" rel="icon" type="image/png">
        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
        <!-- Extra details for Live View on GitHub Pages -->

        <!-- Icons -->
        <link href="${data.nucleo}" rel="stylesheet">
        <link href="${data.css_min}" rel="stylesheet">
        <!-- Argon CSS -->
        <link type="text/css" href="${data.css_argon}" rel="stylesheet">
        <style>
            .card-body {
                font-family: arial;
                padding: 0;
            }
            .col {
                padding-right: 0;
            }
            .custom-row {
                border: 1px solid;
                margin-left: 10px;
                margin-right: 10px;
            }
            .custom-col {
                border-right: 1px solid;
            }
            label {
                margin-bottom: 0;
            }
            .text-muted {
                color: black !important;
            }
            body {
                font-size: 0.71rem;
                color: black;
                font-weight: normal;
            }
            .size-text {
                font-weight: 500;
            }
            .card-title {
                margin: 0;
                font-weight: bold;
                color: black;
                font-size: 16px;
            }
            p {
                margin-bottom: 0;
            }
            .card {
                margin-left: 10px;
                margin-right: 10px;
            }
            @media print {
                .page {
                    page-break-after: always;
                }
            }
            .custom-border {
                border-right: 3px solid black;
            }
            .custom-div {
                width: 50px;
                height: 50px;
                border: 1px solid black;
                border-radius: 18px;
                margin-left: 11%;
            }
            .custom-height {
                height: 120px;
            }

        </style>
        <script type="text/javascript" src="${data.renderCode}"></script>
    </head>
    <body
        onload="window.print()"
    >

    <div class="main-content">`
    data.orders && data.orders.forEach((order, key) => {
        html += `
        <div class="card ${key % 2 > 0 && data.level != data.level_admin ? 'page' : ''}" style="${key % 2 > 0 && data.level != data.level_admin ? 'margin-top: 23px;margin-bottom: 50px;' : 'margin-bottom: 50px;'}">
            <div class="card-body">
                <div class="row custom-row">
                    <div class="col-5 mt-4" >
                        <div class="card-body">
                            <img width="300" src="${data.logo_print}">
                        </div>
                    </div>
                    <div class="col-3 mt-4">
                        <div class="card-body">
                            <h2 class="card-title mt-2 size-text">Hotline: <b>1900 633 656</b></h2>
                            <p class="card-text size-text">Website: www.ht-cargos.com</p>
                            <p class="card-text size-text">Email: info@ht-cargos.com</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card-body text-center">
                            <p class="size-text"><svg id="${order.order_code}"></svg></p>
                        </div>
                    </div>
                </div>


                <div class="row custom-row">
                    <div class="col custom-col">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <h4 class="card-title">Họ tên, địa chỉ người gửi: </h4>
                                    <p class="size-text" >${order.sender && order.sender.sender_name ? order.sender.sender_name : '.....' }</p>
                                    <p class="size-text" >${order.sender ? (order.sender.address ? order.sender.address + ', ' : '') + (order.sender.ward ? order.sender.ward.ward_name + ', ' : '') + (order.sender.district ? order.sender.district.district_name + ', ' : '') + (order.sender.city ? order.sender.city.city_name : '' ) : '.....'}</p>
                                </div>
                                <div class="col-6">
                                    <h4 class="card-title">Mã KH </h4>
                                    <p class="size-text" >${order && order.user && order && order.user.name ? order.user.name : ''}</p>
                                </div>
                            </div>
                            <p class="card-text"><h4 class="card-title">Phòng ban:</h4> ${order.department != null ? order.department : ''}</p>
                            <p class="size-text"><h4 class="card-title">Điện thoại: ${order.sender && order.sender.sender_phone ? order.sender.sender_phone : '.....'}</h4></p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card-body">

                            <div class="row">
                                <div class="col-8" style="margin-bottom: 5px">
                                    <h4 class="card-title">${data.service_domestic && data.service_domestic.name}</h4>
                                    <div class="row">`
                                    for (const key in (data.service_domestic && data.service_domestic.value)) {
                                        if (!data.service_domestic.value.hasOwnProperty(key)) continue;
                                        const service = data.service_domestic.value[key];
                                        const isChecked = order.services.find(item => item.service == key);
                                        html += `
                                            <div class="col-md-4" style="margin-left: 20px">
                                                <input type="checkbox" ${isChecked ? 'checked' : ''} class="form-check-input">
                                                <label for="check1">${service}</label>
                                            </div>
                                        `
                                    }
                                    html +=`</div>
                                </div>
                                <div class="col-4">
                                    <h4 class="card-title">${data.service_international && data.service_international.name}</h4>
                                    <div class="row">`
                                    for (const key in (data.service_international && data.service_international.value)) {
                                        if (!data.service_international.value.hasOwnProperty(key)) continue;
                                        const service = data.service_international.value[key];
                                        const isChecked = order.services.find(item => item.service == key);
                                        html += `
                                            <div class="col-md-12" style="margin-left: 20px">
                                                <input type="checkbox" ${isChecked ? 'checked' : ''} class="form-check-input">
                                                <label>${service}</label>
                                            </div>
                                        `
                                    }
                                    html += `</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row custom-row">
                    <div class="col custom-col">
                        <div class="card-body">
                            <h4 class="card-title">Họ tên, địa chỉ người nhận: </h4>
                            <p class="card-text size-text">${order.receiver && order.receiver.receiver_name ? order.receiver.receiver_name : '.....'}</p>

                            <p class="card-text size-text"><small class="text-muted">${order.receiver ? (order.receiver.address ? order.receiver.address + ', ' : '' ) + (order.receiver.ward ? order.receiver.ward.ward_name + ', ' : '')  + (order.receiver.district ? order.receiver.district.district_name + ', ' : '') + (order.receiver.city ? order.receiver.city.city_name : '') : '.....'}</small></p>

                            <p class="card-text size-text"><h4 class="card-title">Điện thoại: ${order.receiver && order.receiver.receiver_phone ? order.receiver.receiver_phone : '.....'}</h4></p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h4 class="card-title">${data.service_extra && data.service_extra.name}</h4>
                                    <div class="row">`
                                    for (const key in (data.service_extra && data.service_extra.value)) {
                                        if (!data.service_extra.value.hasOwnProperty(key)) continue;
                                        const service = data.service_extra.value[key];
                                        const isChecked = order.services.find(item => item.service == key);
                                        html += `
                                            <div class="col-md-3" style="margin-left: 20px">
                                                <input type="checkbox" ${isChecked ? 'checked' : ''} class="form-check-input">
                                                <label>${service}</label>
                                            </div>
                                        `
                                    }
                                    html +=`</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <h4 class="card-title">Khai báo nội dung và số lượng gửi:</h4>
                                    <p class="size-text">${order.note ? order.note : ''}</p>
                                </div>
                            </div>
                            <p style="margin-top: 20px" class="size-text">Giá trị hàng hóa: ${order.total ? order.total : ''}</p>
                        </div>
                    </div>
                </div>

                <div class="row custom-row">
                    <div class="col custom-col">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-7">
                                    <h4 class="card-title">Ký xác nhận người gửi hàng: </h4>
                                    <p class="card-text size-text"><b>Ngày gửi:</b> ${converDate(order.order_date)} </p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="card-text size-text" style="margin-bottom: 100px">Ký ghi rõ họ tên người gửi </p>
                                        </div>
                                        <div class="col-md-6 custom-border">
                                            <p class="card-text size-text">Dấu ngày gửi</p>
                                            <div class="mt-5 custom-div"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <h4 class="card-title">Thông tin giao nhận</h4>
                                    <p class="card-text size-text">Ngày phát:..............</p>
                                    <p class="card-text size-text">Nv phát:..............</p>
                                    <p class="card-text size-text" style="margin-bottom: 100px">Ký ghi rõ họ tên người nhận</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-7">
                                    <p class="card-text size-text">NV-Chấp nhận:..............</p>
                                </div>
                                <div class="col-md-5">
                                    <p class="card-text size-text">Bộ phận:..............</p>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col">
                        <div class="card-body">

                            <div class="row">
                                <div class="col-md-7">
                                    <div class="row">
                                        <div class="col">
                                            <h4 class="card-title">Thông tin hàng hóa</h4>
                                            <div class="row">
                                                <div class="col-md-4 size-text custom-height custom-border">Số kiện</div>
                                                <div class="col-md-8 size-text">Trọng lượng thực tế <p class="col-5 text-center"> ${(order.weight ? order.weight : 0) + ' g'}</p></div>

                                            </div>
                                            <p class="size-text" style="margin-left: 30px">Kích thước (${(order.height ? order.height : 0) + ' x '+ (order.long ? order.long : 0) + ' x '+ (order.width ? order.width : 0) +' cm'}${(order.height ? order.height : 0) + ' x '+ (order.long ? order.long : 0) + ' x '+ (order.width ? order.width : 0) +' cm'})</p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <h4 class="card-title" >Hình thức thanh toán</h4>
                                            <div class="row">`
                                            for (const key in data.payment_method) {
                                                if (!data.payment_method.hasOwnProperty(key)) continue;
                                                html += `
                                                    <div class="col-md-4" style="margin-left: 20px">
                                                        <input type="checkbox" ${order.payment_method == key ? 'checked' : ''} class="form-check-input">
                                                        <label class="size-text">${data.payment_method[key]}</label>
                                                    </div>
                                                `
                                            }
                                            html +=`</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <h4 class="card-title size-text">Trọng lượng thanh toán</h4>
                                    <p class="size-text">Trọng lượng tính cước</p>
                                    <p>...........................................</p>
                                    <p class="size-text">Cước phí:.........................</p>
                                    <p class="size-text">Phí khác:..........................</p>
                                    <p class="size-text">VAT:...................................</p>
                                    <p class="size-text">Bảo hiểm:........................</p>
                                    <p class="size-text">Tổng cộng:......................</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`
        if(data.level == data.level_admin) {
            html += `<div class="card page" style="margin-top: 23px">
                    <div class="card-body">
                        <div class="row custom-row">
                            <div class="col-5 mt-4" >
                                <div class="card-body">
                                    <img width="300" src="${data.logo_print}">
                                </div>
                            </div>
                            <div class="col-3 mt-4">
                                <div class="card-body">
                                    <h2 class="card-title size-text">Hotline: <b>1900 633 656</b></h2>
                                    <p class="card-text size-text">Website: www.ht-cargos.com</p>
                                    <p class="card-text size-text">Email: info@ht-cargos.com</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card-body text-center">
                                    <p class="size-text"><svg id="${order.order_code + data.level_admin}"></svg></p>
                                </div>
                            </div>
                        </div>


                        <div class="row custom-row">
                            <div class="col custom-col">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <h4 class="card-title">Họ tên, địa chỉ người gửi: </h4>
                                            <p class="size-text" >${order.sender && order.sender.sender_name ? order.sender.sender_name : '.....' }</p>
                                            <p class="size-text" >${order.sender ? (order.sender.address ? order.sender.address + ', ' : '') + (order.sender.ward ? order.sender.ward.ward_name + ', ' : '') + (order.sender.district ? order.sender.district.district_name + ', ' : '') + (order.sender.city ? order.sender.city.city_name : '' ) : '.....'}</p>
                                        </div>
                                        <div class="col-6">
                                            <h4 class="card-title">Mã KH </h4>
                                            <p class="size-text" >${order && order.user && order && order.user.name ? order.user.name : ''}</p>
                                        </div>
                                    </div>

                                    <p class="card-text"><h4 class="card-title">Phòng ban:</h4> ${order.department != null ? order.department : ''}</p>
                                    <p class="size-text"><h4 class="card-title">Điện thoại: ${order.sender && order.sender.sender_phone ? order.sender.sender_phone : '.....'}</h4></p>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card-body">

                                    <div class="row">
                                        <div class="col-8" style="margin-bottom: 5px">
                                            <h4 class="card-title">${data.service_domestic && data.service_domestic.name}</h4>
                                            <div class="row">`
                                            for (const key in (data.service_domestic && data.service_domestic.value)) {
                                                if (!data.service_domestic.value.hasOwnProperty(key)) continue;
                                                const service = data.service_domestic.value[key];
                                                const isChecked = order.services.find(item => item.service == key);
                                                html += `
                                                    <div class="col-md-4" style="margin-left: 20px">
                                                        <input type="checkbox" ${isChecked ? 'checked' : ''} class="form-check-input">
                                                        <label for="check1">${service}</label>
                                                    </div>
                                                `
                                            }
                                            html +=`</div>
                                        </div>
                                        <div class="col-4">
                                            <h4 class="card-title">${data.service_international && data.service_international.name}</h4>
                                            <div class="row">`
                                            for (const key in (data.service_international && data.service_international.value)) {
                                                if (!data.service_international.value.hasOwnProperty(key)) continue;
                                                const service = data.service_international.value[key];
                                                const isChecked = order.services.find(item => item.service == key);
                                                html += `
                                                    <div class="col-md-12" style="margin-left: 20px">
                                                        <input type="checkbox" ${isChecked ? 'checked' : ''} class="form-check-input">
                                                        <label>${service}</label>
                                                    </div>
                                                `
                                            }
                                            html +=`</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row custom-row">
                            <div class="col custom-col">
                                <div class="card-body">
                                    <h4 class="card-title">Họ tên, địa chỉ người nhận: </h4>
                                    <p class="card-text size-text">${order.receiver && order.receiver.receiver_name ? order.receiver.receiver_name : '.....'}</p>
                                    <p class="card-text"><small class="text-muted size-text">${order.receiver ? (order.receiver.address ? order.receiver.address + ', ' : '' ) + (order.receiver.ward ? order.receiver.ward.ward_name + ', ' : '')  + (order.receiver.district ? order.receiver.district.district_name + ', ' : '') + (order.receiver.city ? order.receiver.city.city_name : '') : '.....'}</small></p>

                                    <p class="card-text"><h4 class="card-title">Điện thoại: ${order.receiver && order.receiver.receiver_phone ? order.receiver.receiver_phone : '.....'}</h4></p>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h4 class="card-title">${data.service_extra && data.service_extra.name}</h4>
                                            <div class="row">`
                                            for (const key in (data.service_extra && data.service_extra.value)) {
                                                if (!data.service_extra.value.hasOwnProperty(key)) continue;
                                                const service = data.service_extra.value[key];
                                                const isChecked = order.services.find(item => item.service == key);
                                                html += `
                                                    <div class="col-md-3" style="margin-left: 20px">
                                                        <input type="checkbox" ${isChecked ? 'checked' : ''} class="form-check-input">
                                                        <label>${service}</label>
                                                    </div>
                                                `
                                            }
                                            html += `</div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <h4 class="card-title">Khai báo nội dung và số lượng gửi:</h4>
                                            <p class="size-text">${order.note ? order.note : ''}</p>
                                        </div>
                                    </div>
                                    <p class="size-text" style="margin-top: 20px">Giá trị hàng hóa: ${order.total ? order.total : ''}</p>
                                </div>
                            </div>
                        </div>

                        <div class="row custom-row">
                            <div class="col custom-col">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-7">
                                            <h4 class="card-title">Ký xác nhận người gửi hàng: </h4>
                                            <p class="card-text size-text"><b>Ngày gửi:</b> ${converDate(order.order_date)} </p>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p class="card-text size-text" style="margin-bottom: 100px">Ký ghi rõ họ tên người gửi </p>
                                                </div>
                                                <div class="col-md-6 custom-border">
                                                    <p class="card-text size-text">Dấu ngày gửi</p>
                                                    <div class="mt-5 custom-div"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <h4 class="card-title">Thông tin giao nhận</h4>
                                            <p class="card-text size-text">Ngày phát:..............</p>
                                            <p class="card-text size-text">Nv phát:..............</p>
                                            <p class="card-text size-text" style="margin-bottom: 100px">Ký ghi rõ họ tên người nhận</p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-7">
                                            <p class="card-text size-text">NV-Chấp nhận:..............</p>
                                        </div>
                                        <div class="col-md-5">
                                            <p class="card-text size-text">Bộ phận:..............</p>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class="col">
                                <div class="card-body">

                                    <div class="row">
                                        <div class="col-md-7">
                                            <div class="row">
                                                <div class="col">
                                                    <h4 class="card-title">Thông tin hàng hóa</h4>
                                                    <div class="row">
                                                        <div class="col-md-4 pb-4 size-text custom-height custom-border">Số kiện</div>
                                                        <div class="col-md-8 size-text">Trọng lượng thức tế <p class="col-5 text-center"> ${(order.weight ? order.weight : 0) + ' g'}</p></div>

                                                    </div>
                                                    <p class="size-text" style="margin-left: 30px">Kích thước (${(order.height ? order.height : 0) + ' x '+ (order.long ? order.long : 0) + ' x '+ (order.width ? order.width : 0) +' cm'})</p>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col">
                                                    <h4 class="card-title" >Hình thức thanh toán</h4>
                                                    <div class="row">`
                                                    for (const key in data.payment_method) {
                                                        if (!data.payment_method.hasOwnProperty(key)) continue;
                                                        html += `
                                                            <div class="col-md-4" style="margin-left: 20px">
                                                                <input type="checkbox" ${order.payment_method == key ? 'checked' : ''} class="form-check-input">
                                                                <label class="size-text">${data.payment_method[key]}</label>
                                                            </div>
                                                        `
                                                    }
                                                    html +=`</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <h4 class="card-title size-text">Trọng lượng thanh toán</h4>
                                            <p class="size-text">Trọng lượng tính cước</p>
                                            <p>...........................................</p>
                                            <p class="size-text">Cước phí:.........................</p>
                                            <p class="size-text">Phí khác:..........................</p>
                                            <p class="size-text">VAT:...................................</p>
                                            <p class="size-text">Bảo hiểm:........................</p>
                                            <p class="size-text">Tổng cộng:......................</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        `
        }

    })
    html += `</div>
        <script type="text/javascript">`
            html += `
            let orders = ${JSON.stringify(data.orders)}
            let level = ${data.level}
            let levelAdmin = ${data.level_admin}
            orders && orders.length > 0 && orders.forEach(order => {
                let idRender = '#'+ order.order_code;
                JsBarcode(idRender, order.order_code, {
                    fontOptions: "bold",
                    height: 90
                });
                if(level == levelAdmin) {
                    let idRenderAdmin = '#'+ order.order_code + levelAdmin;
                    JsBarcode(idRenderAdmin, order.order_code, {
                        fontOptions: "bold",
                        height: 90
                    });
                }
            })
            `
        html +=`</script>
        </body>
        </html>`

    return html;
}

function converDate(date) {
    const data = date.split("-");
    return data[2]+ "/" +data[1]+ "/" +data[0]
}
