<?php
session_start();
ob_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
include "module/PDO.php";
include "module/loai.php";
include "module/sanpham.php";
include "module/account.php";
include "module/comment.php";
include "module/order.php";
include "module/banner.php";
include "module/static.php";
include "module/cupon.php";
include "view/header.php";
require 'module/PHPMailer/src/Exception.php';
require 'module/PHPMailer/src/PHPMailer.php';
require 'module/PHPMailer/src/SMTP.php';

if(isset($_SESSION['cart'])){
    $cart = $_SESSION['cart'];
    // ShowArray($cart);
    //tính tổng
    function calculateTotal() {
        $total = 0;
    
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $product) {
                $total += $product['so_luong'] * $product['giam_gia'];
                // Nếu bạn muốn tính tổng theo giá không giảm giá, thay $product['giam_gia'] bằng $product['don_gia']
            }
        }
    
        return $total;
    }
    function count_cart(){
        $count = 0;
        if(isset($_SESSION['cart'])){
            foreach($_SESSION['cart'] as $product){
                $count += $product['so_luong'];
            }
        }
        return $count;
    }
    $total = calculateTotal();
    $_SESSION['count_cart'] = count_cart();     
}

if(isset($_SESSION['wishlist'])){
    $wish = $_SESSION['wishlist'];
    // ShowArray($wish);
    //tính tổng
    function count_wish(){
        $count = 0;
        if(isset($_SESSION['wishlist'])){
            foreach($_SESSION['wishlist'] as $product){
                $count = count($_SESSION['wishlist']);
            }
        }
        return $count;
    }
    $_SESSION['count_wish'] = count_wish();     
}
include "view/extension/cart.php";
if(isset($_GET['act'])){
    $act=$_GET['act'];
    switch ($act){
        case 'ctsp':{
            if(isset($_GET['ma_san_pham'])){
                $ma_san_pham = $_GET['ma_san_pham'];
                $data = sanphamct_get_byid($ma_san_pham);
                $data1 = sanpham_load_ctbl($ma_san_pham);
                $data2 = sanpham_count_ctbl($ma_san_pham);
                sanpham_add_luotxem($ma_san_pham);
             
            }
            if(isset($_POST['binhluan'])){
                $ma_san_pham=$_POST['masp'];
                $ma_khach_hang=$_SESSION['ma_khach_hang'];
                $noi_dung=$_POST['message'];
                $ngay_binh_luan= date('Y-m-d');
                $anhbl=$_FILES['img']['name'];
                $target_dir = "image/";
                  $target_file = $target_dir . basename($_FILES['img']['name']);
                  if(move_uploaded_file($_FILES['img']['tmp_name'], $target_file)){
  
                 }else{
                    $anhbl=" ";
                 }
                          comment_insert($ma_san_pham,$ma_khach_hang,$noi_dung,$ngay_binh_luan,$anhbl);
                            header("location:index.php?act=ctsp&ma_san_pham=$ma_san_pham");
            }
            include "view/chitietsp/chitetsp.php";
            break;
        }
        case 'cart': {
            if (isset($_POST['addcart'])) {
                $ma_san_pham = $_POST['ma_san_pham'];
                $so_luong = $_POST['so_luong'];
                $ten_san_pham = $_POST['ten_san_pham'];
                if(isset($_POST['size'])){
                    $size = $_POST['size'];
                } else {
                    $size = "M";
                }
                if(isset($_POST['mau'])){
                    $mau = $_POST['mau'];
                } else {
                    $mau = "Đen";
                }
                $don_gia = $_POST['don_gia'];
                $giam_gia = $_POST['giam_gia'];
                $anh = $_POST['anh'];
        
                // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
                $product_exists = false;
                
                if (isset($_SESSION['cart'])) {
                    foreach ($_SESSION['cart'] as $key => $product) {
                        if ($product['ma_san_pham'] == $ma_san_pham) {
                            // Nếu sản phẩm đã tồn tại trong giỏ hàng, tăng số lượng lên
                            $_SESSION['cart'][$key]['so_luong'] += $so_luong;
                            $product_exists = true;
                            header("location:index.php?act=cart");
                            break;
                        }
                    }
                }
        
                // Nếu sản phẩm chưa có trong giỏ hàng, thêm mới
                if (!$product_exists) {
                    $product = array(
                        'ma_san_pham' => $ma_san_pham,
                        'so_luong' => $so_luong,
                        'ten_san_pham' => $ten_san_pham,
                        'size' => $size,
                        'don_gia' => $don_gia,
                        'mau' => $mau,  
                        'giam_gia' => $giam_gia,
                        'anh' => $anh
                    );
        
                    if (!isset($_SESSION['cart'])) {
                        $_SESSION['cart'] = array($product);
                        header("location:index.php?act=cart");
                    } else {
                        $_SESSION['cart'][] = $product;
                        header("location:index.php?act=cart");
                    }
                }
            }
        
            include "view/cart.php";
            break;
        }
        case 'coupon': {
            if (isset($_POST['code'])) {
                //validate
                $error = [];
                if (empty($_POST['magiam'])) {
                    $error['ten_ma_gg'] = "Bạn chưa nhập tên mã giảm giá";
                }
                if(empty($error)){
                    //check vaild
                    $ma_giam_gia = $_POST['magiam'];
                    $data = check_vaild_date($ma_giam_gia);
                    if(!empty($data)){
                        extract($data);
                    
                        if($con_han == 1){
                            $res = get_ma_giam_gia($ma_giam_gia);
                            // ShowArray( $res);
                            extract( $res); 
                            // Áp dụng giảm giá cho giỏ hàng
                            if (isset($_SESSION['cart'])) {
                                $ma_giam_gia = $_POST['magiam'];
                                //kiểm tra mã bị nhập 2 lần
                                if($ma_giam_gia == $_SESSION['ma_giam_gia']){
                                    $error['magiam'] = "Mã giảm giá đã được sử dụng";
                                } else {
                                $_SESSION['ma_giam_gia'] = $ma_giam_gia;    
                                    // Tổng giảm giá từ mã giảm giá
                                $total_discount = $so_tien_giam;
                                $_SESSION['total_discount'] = $total_discount;

                                // Tính tổng giá của giỏ hàng
                                $total_cart = calculateTotal();

                                // Áp dụng giảm giá cho tổng giỏ hàng
                                $total_after_discount = $total_cart - $total_discount;
                                $_SESSION['total_after_discount'] = $total_after_discount;  
                                header("location:index.php?act=checkout");                     
                                }
                               
    
                            }
                            
                        } else {
                            $_SESSION['magiamloi'] = "Mã giảm giá đã hết hạn hoặc không tồn tại";
                            header("location:index.php?act=checkout"); 
                        }
                        


                } else {
                    $_SESSION['magiamloi'] = "Mã giảm giá đã hết hạn hoặc không tồn tại";
                    header("location:index.php?act=checkout"); 
                }

               
            }
           
                }
           
            break;
        }
        case'wishlist':{
            if (isset($_POST['wishlistadd'])) {
                $ma_san_pham = $_POST['ma_san_pham'];
                $so_luong = $_POST['so_luong'];
                $ten_san_pham = $_POST['ten_san_pham'];
                $don_gia = $_POST['don_gia'];
                $giam_gia = $_POST['giam_gia'];
                $anh = $_POST['anh'];
        
                // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
                $product_exists = false;
                //kiểm tra có trong wishlist chưa
                if (isset($_SESSION['wishlist'])) {
                    foreach ($_SESSION['wishlist'] as $key => $product) {
                        if ($product['ma_san_pham'] == $ma_san_pham) {
                            // Nếu sản phẩm đã tồn tại trong giỏ hàng, bỏ qua
                            $product_exists = true;
                           header("location:index.php?act=wishlist");
                           $_SESSION['fall_wish'] = "Sản phẩm đã có trong danh sách mong muốn";
                            break;
                        }
                    }
                }
                // Nếu sản phẩm chưa có trong giỏ hàng, thêm mới
                if (!$product_exists) {
                    $product = array(
                        'ma_san_pham' => $ma_san_pham,
                        'so_luong' => $so_luong,
                        'ten_san_pham' => $ten_san_pham,
                        'don_gia' => $don_gia,
                        'giam_gia' => $giam_gia,
                        'anh' => $anh
                    );
                    if (!isset($_SESSION['wishlist'])) {
                        $_SESSION['wishlist'] = array($product);
                        header("location:index.php?act=wishlist");
                    } else {
                        $_SESSION['wishlist'][] = $product;
                        header("location:index.php?act=wishlist");
                    }

                }
            }      
            if(isset($_POST['dellprod'])){
                $ma_san_pham_xoa = $_POST['ma_san_pham'];
            
                // Tìm kiếm và xóa sản phẩm khỏi giỏ hàng trong $_SESSION
                if (isset($_SESSION['wishlist'])) {
                    foreach ($_SESSION['wishlist'] as $key => $product) {
                        if ($product['ma_san_pham'] == $ma_san_pham_xoa) {
                            unset($_SESSION['wishlist'][$key]);
                            header("location:index.php?act=wishlist");
                        }
                    }
                }
            }
            include "view/wishlist.php";
            break;
    
     }
        case 'ifogor':{
            if(isset($_POST['submit'])){
                //do validate   
                $error = [];
                if(empty($_POST['email'])){
                    $error['email'] = "Bạn chưa nhập email";
                }
                //check vaild email
                if(empty($error)){
                    $email = htmlspecialchars($_POST['email']);
                    $data = account_check_email($email);
                    if(empty($data)){
                        $error['email'] = "Email không tồn tại";
                    } else {
                        $data = account_email_info($email);
                        if($data){
                            extract($data);
                            $encrypted_session = base64_encode($email);
                            $link = "act=resetpass&session=$encrypted_session";
                            $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
                            try {
                                //Server settings
                                $mail->SMTPDebug = 2;                               // Enable verbose debug output
                                $mail->isSMTP();                                      // Set mailer to use SMTP
                                $mail->Host = 'smtp.office365.com';  // Specify main and backup SMTP servers
                                $mail->SMTPAuth = true;                               // Enable SMTP authentication
                                $mail->Username = 'akonda4543@outlook.com';           // Your Outlook email address
                                $mail->Password = 'B@2004.com';              // Your Outlook email password
                                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                                $mail->Port = 587;  
                                $mail->CharSet = 'UTF-8';                                    // TCP port to connect to
                                //Recipients
                                $mail->setFrom("akonda4543@outlook.com", 'Crown Store');
                                $mail->addAddress("$email");     // Add a recipient
                                // Content
                                $mail->isHTML(true);                                  // Set email format to HTML
                                $mail->Subject = 'Bạn vừa yêu cầu đổi mật khẩu  ';
                                $mail->Body    = "<h2>Bạn vừa yêu cầu đổi mật khẩu</h2> <br> <b>Đây là liên kết để đổi mật khẩu qua email. Link này có hiệu lực 24h kể từ lúc email này được gửi</b><br> http://localhost/DU_AN_1/index.php?$link <br>nếu không phải bạn ? vui lòng bỏ qua email này";
                                $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
                                $mail->send();
                            } catch (Exception $e) {
                                echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
                            }
                            
                                $_SESSION['sussec'] = "Gửi liên kết thành công";
                         
                                                      
                    }                          

            }
                }
            }

                include "view/khachhang/ifogor.php";
                break;

       
    }
    case 'resetpass':{
        $session_param = $_GET['session'];
        $decrypted_session = base64_decode($session_param);
        if(!isset($_SESSION['veri_email'])){
            $_SESSION['faill_login'] = "Đường dẫn không hợp lệ";
            unset($_SESSION['ten_khach_hang']);
            unset($_SESSION['email']);  
            unset($_SESSION['ma_khach_hang']);      
            unset($_SESSION['$avt']);   
            unset($_SESSION['veri_email']);
            header("location:index.php?act=login");
        } else {
        if ($decrypted_session === $_SESSION['veri_email']) {
            $email = $_SESSION['veri_email'];
            $data =  account_email_info($email);
            extract($data);
            ShowArray($data);
          
            if($data){
                extract($data);
                if(isset($_POST['submit'])){
                    $error = [];
                    if(empty($_POST['password'])){
                        $error['password'] = "Bạn chưa nhập mật khẩu";
                    }
                    if(empty($_POST['repassword'])){
                        $error['repassword'] = "Bạn chưa nhập lại mật khẩu";
                    }
                    if(empty($error)){
                        $password = $_POST['password'];
                        $repassword = $_POST['repassword'];
                        if($password != $repassword){
                            $error['repassword'] = "Mật khẩu không trùng khớp";
                        } else {
                            $data = account_update_password($password,$ma_khach_hang);
                            if($data){
                                $_SESSION['sussec'] = "Đổi mật khẩu thành công";
                                unset($_SESSION['veri_email']);
                                header("location:index.php?act=login");
                            }
                        }
                    }
                }
            }
        }
        else {
            echo "Lỗi";
        }

    }
        include "view/khachhang/resetpass.php";
        break;
    }
        case'cartupdate':{
            if (isset($_POST['dellprod'])) {
                $ma_san_pham_xoa = $_POST['ma_san_pham'];
            
                // Tìm kiếm và xóa sản phẩm khỏi giỏ hàng trong $_SESSION
                if (isset($_SESSION['cart'])) {
                    foreach ($_SESSION['cart'] as $key => $product) {
                        if ($product['ma_san_pham'] == $ma_san_pham_xoa) {
                            unset($_SESSION['cart'][$key]);
                            header("location:index.php?act=cart");
                        }
                    }
                }
            }
            
            // Xử lý khi người dùng ấn nút "Xóa toàn bộ giỏ hàng"
            if (isset($_POST['clearallcart'])) {
                // Xóa toàn bộ giỏ hàng trong $_SESSION
                unset($_SESSION['cart']);
                unset($_SESSION['total_discount']);
                unset($_SESSION['ma_giam_gia']);
                unset($_SESSION['total_after_discount']);   
                header("location:index.php?act=cart");
            }
            
                    // Chỉ xử lý khi người dùng nhấn nút "Cập nhật giỏ hàng" từ trang cart
                    if (isset($_POST['updatecart'])) {
                        // Lặp qua sản phẩm trong giỏ hàng và cập nhật số lượng
                        if (isset($_SESSION['cart'])) {
                            foreach ($_SESSION['cart'] as $key => $product) {
                                $new_quantity = $_POST['so_luong'][$key];
                                
                                // Kiểm tra nếu số lượng mới là 0, thì xóa sản phẩm khỏi giỏ hàng
                                if ($new_quantity == 0) {
                                    unset($_SESSION['cart'][$key]);
                                    header("location:index.php?act=cart");
                                } else {
                                    // Cập nhật số lượng
                                    $_SESSION['cart'][$key]['so_luong'] = $new_quantity;
                                    header("location:index.php?act=cart");
                                }
                            }
                        }
                    }
            include "view/cart.php";
            break;
    
        }
        case 'order':{
            include "view/order.php";
            break;
        }
        case 'login':{
            if(isset($_POST['submit'])){
                $error = [];
                if(empty($_POST['email'])){
                    $error['email'] = "Bạn chưa nhập email";
                }
                if(empty($_POST['password'])){
                    $error['password'] = "Bạn chưa nhập mật khẩu";
                }
                if(empty($error)){
                    $email = $_POST['email'];
                    $password = $_POST['password'];
                    $data = account_user_login($email,$password);
            if($data){
                   extract($data);
                if($email == $email && $mat_khau == $password){
                    $_SESSION['ten_khach_hang'] = $ten_khach_hang;
                    $_SESSION['email'] = $email;
                    $_SESSION['ma_khach_hang'] = $ma_khach_hang;    
                    $_SESSION['$avt'] = $avt;
                    header("location:index.php");
                } else {
                    $error['login'] = "Mật khẩu hoặc tài khoản không đúng";
                }
            } else {
                $error['login'] = "Mật khẩu hoặc tài khoản không đúng";
            }
        
                }
            }
            //check cart isset
               if(isset($_SESSION['cart'])&&isset($_SESSION['ten_khach_hang'])){
                    header("location:index.php?act=checkout");
               }
               
            include "view/khachhang/login.php";
            break;
        }
        case 'register':{
            if(isset($_POST['submit'])){
                $error = [];
                if(empty($_POST['email'])){
                    $error['email'] = "Bạn chưa nhập email";
                }
                if(empty($_POST['password'])){
                    $error['password'] = "Bạn chưa nhập mật khẩu";
                }
                if(empty($_POST['repassword'])){
                    $error['repassword'] = "Bạn chưa nhập lại mật khẩu";
                }
                if(empty($_POST['hoten'])){
                    $error['hoten'] = "Bạn chưa nhập họ tên";
                }
                if(empty($_POST['phonenumber'])){
                    $error['phonenumber'] = "Bạn chưa nhập số điện thoại";
                }
                if(empty($_POST['agree'])){
                    $error['agree'] = "Bạn chưa đồng ý điều khoản";
                }
                if(empty($error)){
                    $email = $_POST['email'];
                    $password = $_POST['password'];
                    $repassword = $_POST['repassword'];
                    $hoten = $_POST['hoten'];
                    $phonenumber = $_POST['phonenumber'];
                    $data = account_check_email($email);
                    if($data){
                        $error['email'] = "Email đã tồn tại";
                    } else {
                        if($password != $repassword){
                            $error['repassword'] = "Mật khẩu không trùng khớp";
                        } else {
                            $data = account_user_register($email,$password,$hoten,$phonenumber);
                            $_SESSION['veri_email'] = $email;
                            $encrypted_session = base64_encode($email);
                            $link = "act=verified&session=$encrypted_session";
                            $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
                            try {
                                //Server settings
                                $mail->SMTPDebug = 0;                               // Enable verbose debug output
                                $mail->isSMTP();                                      // Set mailer to use SMTP
                                $mail->Host = 'smtp.office365.com';  // Specify main and backup SMTP servers
                                $mail->SMTPAuth = true;                               // Enable SMTP authentication
                                $mail->Username = 'akonda4543@outlook.com';           // Your Outlook email address
                                $mail->Password = 'B@2004.com';              // Your Outlook email password
                                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                                $mail->Port = 587;  
                                $mail->CharSet = 'UTF-8';                                    // TCP port to connect to
                                //Recipients
                                $mail->setFrom("akonda4543@outlook.com", 'Crown Store');
                                $mail->addAddress("$email");     // Add a recipient
                                // Content
                                $mail->isHTML(true);                                  // Set email format to HTML
                                $mail->Subject = 'Xác thực tài khoản';
                                $mail->Body    = "<h2>Bạn vừa yêu đăng ký tài khoản mới website</h2> <br> <b>Đây là liên kết để xác thực tài khoản qua email. Link này có hiệu lực 24h kể từ lúc email này được gửi</b><br> http://localhost/DU_AN_1/index.php?$link <br>nếu không phải bạn ? vui lòng bỏ qua email này";
                                $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
                                $mail->send();
                            } catch (Exception $e) {
                                echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
                            }
                            if($data){
                                $_SESSION['sussec'] = "Đăng ký thành công";
                                header("location:index.php?act=login");
                            }
                        }
                           
                        }
                    }
                 
                }
            
            include "view/khachhang/register.php";
            break;
        }
        case 'account':{
            if(!isset($_SESSION['ten_khach_hang'])){
                $_SESSION['faill_login'] = "Bạn chưa đăng nhập";
                header("location:index.php?act=login");

            }
            if(isset($_POST['submit'])){
                $error = [];
                if(empty($_POST['name'])){
                    $error['hoten'] = "Bạn chưa nhập họ tên";
                }
                if(empty($_POST['sdt'])){
                    $error['phonenumber'] = "Bạn chưa nhập số điện thoại";
                }
                if(empty($_POST['diachi'])){
                    $error['diachi'] = "Bạn chưa nhập địa chỉ";
                }
                if(empty($_POST['email'])){
                    $error['email'] = "Bạn chưa nhập email";
                }
                if(empty($error)){
                    $ten_khach_hang = $_POST['name'];
                    $sdt = $_POST['sdt'];
                    $dia_chi = $_POST['diachi'];
                    $email = $_POST['email'];
                    $ma_khach_hang = $_POST['makh'];;
                    $avt = $_FILES['avt']['name'];
                    //check image
                            if(empty($_FILES['avt']['name'])){
                                $avt = $data['avt'];
                            } else {
                                $target_dir = "../image/";  
                                $target_file = $target_dir . basename($_FILES["avt"]["name"]);
                                if (move_uploaded_file($_FILES["avt"]["tmp_name"], $target_file)) {
                                    // echo "File " . htmlspecialchars(basename($_FILES["avt"]["name"])) . " đã được tải lên.";
                                } else {
                                    // echo "deafut = noimage.jpg";
                                }
                            }
                        account_update($ten_khach_hang,$dia_chi,$sdt,$email,$avt,$ma_khach_hang);
                  
                        $_SESSION['sussec'] = "Cập nhật thành công";
                        header("location:index.php?act=account");
                }
                

            }

            if(isset($_POST['updatepassword'])){
                $error = [];
                if(empty($_POST['oldpass'])){
                    $error['password'] = "Bạn chưa nhập mật khẩu";
                }   
                if(empty($_POST['newpass'])){
                    $error['newpassword'] = "Bạn chưa nhập mật khẩu mới";
                }
                if(empty($_POST['repass'])){
                    $error['repassword'] = "Bạn chưa nhập lại mật khẩu";
                }
                if(empty($error)){
                    $oldpass = $_POST['oldpass'];
                    $newpassword = $_POST['newpass'];
                    $repassword = $_POST['repass'];
                    $ma_khach_hang = $_SESSION['ma_khach_hang'];
                    $data = account_get_password($ma_khach_hang);
                    if($data){
                        extract($data);
                        if($oldpass == $mat_khau){
                            if($newpassword != $repassword){
                                $error['repassword'] = "Mật khẩu không trùng khớp";
                            } else {
                                $data = account_update_password($newpassword,$ma_khach_hang);
                                if($data){
                                    $_SESSION['sussec'] = "Cập nhật thành công";
                                    header("location:index.php?act=account");
                                }
                            }
                        } else {
                            $error['oldpass'] = "Mật khẩu không đúng";
                        }
                    }
                }

            }

            include "view/khachhang/account.php";
            break;
        }
        case 'veriacc':{
            if(isset($_GET['email'])){
                $email = $_GET['email'];            
                $_SESSION['veri_email'] = $email;
                $encrypted_session = base64_encode($email);
                $link = "act=verified&session=$encrypted_session";
                $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
                try {
                    //Server settings
                    $mail->SMTPDebug = 0;                               // Enable verbose debug output
                    $mail->isSMTP();                                      // Set mailer to use SMTP
                    $mail->Host = 'smtp.office365.com';  // Specify main and backup SMTP servers
                    $mail->SMTPAuth = true;                               // Enable SMTP authentication
                    $mail->Username = 'akonda4543@outlook.com';           // Your Outlook email address
                    $mail->Password = 'B@2004.com';              // Your Outlook email password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                    $mail->Port = 587;  
                    $mail->CharSet = 'UTF-8';                                    // TCP port to connect to
                    //Recipients
                    $mail->setFrom("akonda4543@outlook.com", 'Crown Store');
                    $mail->addAddress("$email");     // Add a recipient
                    // Content
                    $mail->isHTML(true);                                  // Set email format to HTML
                    $mail->Subject = 'Xác thực tài khoản';
                    $mail->Body    = "<h2>Bạn vừa yêu đăng ký tài khoản mới website</h2> <br> <b>Đây là liên kết để xác thực tài khoản qua email. Link này có hiệu lực 24h kể từ lúc email này được gửi</b><br> http://localhost/DU_AN_1/index.php?$link <br>nếu không phải bạn ? vui lòng bỏ qua email này";
                    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
                    $mail->send();
                } catch (Exception $e) {
                    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
                }
               
                    $_SESSION['sussecc'] = "chúng tôi đã gửi liên kết xác thực tài khoản qua email của bạn";    
                    header("location:index.php?act=account");
                    
    
            }
         
            break;
        }
        case 'verified':{
            $session_param = $_GET['session'];
            $decrypted_session = base64_decode($session_param);
            if(!isset($_SESSION['veri_email'])){
                $_SESSION['faill_login'] = "Đường dẫn không hợp lệ";
                unset($_SESSION['ten_khach_hang']);
                unset($_SESSION['email']);  
                unset($_SESSION['ma_khach_hang']);      
                unset($_SESSION['$avt']);   
                unset($_SESSION['veri_email']);
                header("location:index.php?act=login");
            } else {
            if ($decrypted_session === $_SESSION['veri_email']) {
                $email = $_SESSION['veri_email'];
                $data =  account_email_info($email);
            if(is_array($data)){
                account_set_verified($email);
                unset($_SESSION['ten_khach_hang']);
                unset($_SESSION['email']);  
                unset($_SESSION['ma_khach_hang']);      
                unset($_SESSION['$avt']);   
                unset($_SESSION['veri_email']);
                $_SESSION['sussec'] = "Xác thực thành công vui lòng đăng nhập lại";
                header("location:index.php?act=login");
            }
            } else {
                echo "Lỗi";
            }
    
    }
    
        break;
    
    }
        case 'chitietdh':{
            if(!isset($_SESSION['ten_khach_hang'])){
                $_SESSION['faill_login'] = "Bạn chưa đăng nhập";
                header("location:index.php?act=login");

            }
            if(isset($_GET['ma_don_hang'])){
                $ma_don_hang=$_GET['ma_don_hang'];
                $data = donhang_get_chi_tiet($ma_don_hang);
            }
            include "view/khachhang/ctdonhang.php";
            break;  
        }
        case 'search': {
        
            if (isset($_POST['submit'])) {
                $key = $_POST['key'];
                $_SESSION['kwords'] = $key;
                $data = san_pham_search($key);
            }
        
            if (isset($_POST['odercode'])) {
                $odercode = $_POST['orderby'];
                $key = $_SESSION['kwords'];

                if($odercode == 1){
                    $data = san_pham_fliter_by_view($_SESSION['kwords']);
                } else if($odercode == 2){
                    $data = san_pham_fliter_by_new($_SESSION['kwords']);
                } else if($odercode == 3){
                    $data =  san_pham_fliter_by_gia_lowtohigh($_SESSION['kwords']);
                } elseif($odercode == 4){
                    $data = san_pham_fliter_by_gia_higtolow($_SESSION['kwords']);  
                }
                 else {
                    $data = san_pham_search($_SESSION['kwords']);
                }
            } 
        
            include "view/search/list.php";
            break;
        }
        case 'listspdm':{
            if(isset($_GET['ma_loai'])){
                if(is_numeric($_GET['ma_loai'])){
                    $ma_loai = $_GET['ma_loai'];
                    $data = list_sanpham_by_danhmuchot($ma_loai);
                } else {
                   $data = sanpham_list();
                }
            }
            if (isset($_POST['odercode'])) {
                $odercode = $_POST['orderby'];
                if($odercode == 1){
                    $data = list_sanpham_by_danhmuc_by_luotxem($ma_loai);
                } else if($odercode == 2){
                    $data = list_sanpham_by_danhmuc_by_new($ma_loai);
                } else if($odercode == 3){
                    $data =  list_sanpham_by_danhmuc_by_gia_lowtohigh($ma_loai);
                } elseif($odercode == 4){
                    $data = list_sanpham_by_danhmuc_by_gia_hightolow($ma_loai);  
                }
                 else {
                    $data = sanpham_list();
                }
                
            } 
        
            include "view/listsanpham/list.php";
            break;
        }