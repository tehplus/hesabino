<?php
/**
 * توابع عمومی مورد نیاز برنامه
 * 
 * @package HesabinoAccounting
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * تغییر اعداد انگلیسی به فارسی
 * 
 * @param string|int $input عدد یا رشته ورودی
 * @return string عدد یا رشته با ارقام فارسی
 */
function toFa($input) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($english, $persian, $input);
}

/**
 * تغییر اعداد فارسی به انگلیسی
 * 
 * @param string|int $input عدد یا رشته ورودی
 * @return string عدد یا رشته با ارقام انگلیسی
 */
function toEn($input) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($persian, $english, $input);
}

/**
 * فرمت کردن عدد با جداکننده هزارگان
 * 
 * @param int|float $number عدد ورودی
 * @param int $decimals تعداد اعشار
 * @param bool $convertToFa تبدیل به فارسی
 * @return string عدد فرمت شده
 */
function formatNumber($number, $decimals = 0, $convertToFa = true) {
    $formatted = number_format($number, $decimals, DECIMAL_SEPARATOR, THOUSAND_SEPARATOR);
    return $convertToFa ? toFa($formatted) : $formatted;
}

/**
 * تبدیل تاریخ میلادی به شمسی
 * 
 * @param string $date تاریخ میلادی (Y-m-d)
 * @param string $format فرمت خروجی
 * @return string تاریخ شمسی
 */
function toJalali($date, $format = 'Y/m/d') {
    if (empty($date)) return '';
    
    $timestamp = strtotime($date);
    $date = date('Y-m-d', $timestamp);
    list($year, $month, $day) = explode('-', $date);
    
    require_once BASEPATH . '/includes/jdf.php';
    $jalali = gregorian_to_jalali($year, $month, $day);
    
    $output = $format;
    $output = str_replace('Y', $jalali[0], $output);
    $output = str_replace('m', str_pad($jalali[1], 2, '0', STR_PAD_LEFT), $output);
    $output = str_replace('d', str_pad($jalali[2], 2, '0', STR_PAD_LEFT), $output);
    
    return toFa($output);
}

/**
 * تبدیل تاریخ شمسی به میلادی
 * 
 * @param string $date تاریخ شمسی (Y/m/d)
 * @return string تاریخ میلادی (Y-m-d)
 */
function toGregorian($date) {
    if (empty($date)) return '';
    
    $date = toEn($date);
    $parts = preg_split('/[-\/ ]/', $date);
    if (count($parts) !== 3) return '';
    
    require_once BASEPATH . '/includes/jdf.php';
    $gregorian = jalali_to_gregorian($parts[0], $parts[1], $parts[2]);
    
    return sprintf(
        '%04d-%02d-%02d',
        $gregorian[0],
        $gregorian[1],
        $gregorian[2]
    );
}

/**
 * فرمت کردن تاریخ و زمان
 * 
 * @param string $datetime تاریخ و زمان
 * @param string $format فرمت خروجی
 * @param bool $convertToJalali تبدیل به شمسی
 * @return string تاریخ و زمان فرمت شده
 */
function formatDateTime($datetime, $format = 'Y/m/d H:i', $convertToJalali = true) {
    if (empty($datetime)) return '';
    
    $timestamp = strtotime($datetime);
    if ($convertToJalali) {
        $date = date('Y-m-d', $timestamp);
        $time = date('H:i:s', $timestamp);
        
        $jalali = toJalali($date);
        return toFa($jalali . ' ' . $time);
    }
    
    return date($format, $timestamp);
}

/**
 * تولید رشته تصادفی
 * 
 * @param int $length طول رشته
 * @param string $type نوع کاراکترها (alpha|numeric|alphanumeric)
 * @return string رشته تصادفی
 */
function generateRandomString($length = 10, $type = 'alphanumeric') {
    $characters = '';
    
    switch ($type) {
        case 'alpha':
            $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        case 'numeric':
            $characters = '0123456789';
            break;
        case 'alphanumeric':
        default:
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
    }
    
    $result = '';
    $max = strlen($characters) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $result .= $characters[random_int(0, $max)];
    }
    
    return $result;
}

/**
 * تمیز کردن متن ورودی
 * 
 * @param string $input متن ورودی
 * @param bool $allowHtml اجازه HTML
 * @return string متن تمیز شده
 */
function cleanInput($input, $allowHtml = false) {
    if (is_array($input)) {
        return array_map(function($item) use ($allowHtml) {
            return cleanInput($item, $allowHtml);
        }, $input);
    }
    
    if ($allowHtml) {
        // فیلتر کردن HTML
        return htmlspecialchars(
            strip_tags(
                $input,
                '<p><a><b><i><u><strong><em><br><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><pre><code>'
            ),
            ENT_QUOTES,
            'UTF-8'
        );
    }
    
    // حذف HTML
    return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
}

/**
 * محدود کردن طول متن
 * 
 * @param string $text متن ورودی
 * @param int $length حداکثر طول
 * @param string $append متن پایانی
 * @return string متن کوتاه شده
 */
function truncate($text, $length = 100, $append = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $append;
}

/**
 * تولید slug از متن
 * 
 * @param string $text متن ورودی
 * @return string slug
 */
function generateSlug($text) {
    // تبدیل به حروف کوچک
    $text = mb_strtolower($text);
    
    // حذف کاراکترهای خاص
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    
    // حذف - های اضافی
    $text = trim($text, '-');
    
    // تبدیل حروف فارسی/عربی به انگلیسی
    $persian = ['ا', 'ب', 'پ', 'ت', 'ث', 'ج', 'چ', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'ژ', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ک', 'گ', 'ل', 'م', 'ن', 'و', 'ه', 'ی'];
    $english = ['a', 'b', 'p', 't', 's', 'j', 'ch', 'h', 'kh', 'd', 'z', 'r', 'z', 'zh', 's', 'sh', 's', 'z', 't', 'z', 'a', 'gh', 'f', 'gh', 'k', 'g', 'l', 'm', 'n', 'v', 'h', 'y'];
    $text = str_replace($persian, $english, $text);
    
    // حذف کاراکترهای غیرمجاز
    $text = preg_replace('~[^-\w]+~', '', $text);
    
    return $text;
}

/**
 * تبدیل اندازه فایل به فرمت خوانا
 * 
 * @param int $bytes اندازه به بایت
 * @param int $precision دقت اعشار
 * @return string اندازه فرمت شده
 */
function formatFileSize($bytes, $precision = 2) {
    $units = ['بایت', 'کیلوبایت', 'مگابایت', 'گیگابایت', 'ترابایت'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return toFa(round($bytes, $precision) . ' ' . $units[$pow]);
}

/**
 * تولید آدرس کامل
 * 
 * @param string $path مسیر نسبی
 * @return string آدرس کامل
 */
function url($path = '') {
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * تولید آدرس فایل در پوشه assets
 * 
 * @param string $path مسیر فایل
 * @return string آدرس کامل
 */
function asset($path) {
    return url('assets/' . ltrim($path, '/'));
}

/**
 * تولید CSRF توکن
 * 
 * @return string توکن
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    // بررسی انقضای توکن
    if (time() - $_SESSION['csrf_token_time'] > CSRF_EXPIRY) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * بررسی معتبر بودن CSRF توکن
 * 
 * @param string $token توکن ارسالی
 * @return bool
 */
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // بررسی انقضای توکن
    if (time() - $_SESSION['csrf_token_time'] > CSRF_EXPIRY) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * ارسال ایمیل
 * 
 * @param string $to آدرس گیرنده
 * @param string $subject موضوع
 * @param string $message متن پیام
 * @param bool $isHtml آیا متن HTML است
 * @return bool
 */
function sendMail($to, $subject, $message, $isHtml = true) {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8',
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
        'Reply-To: ' . MAIL_REPLY_TO,
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

/**
 * تولید breadcrumb
 * 
 * @param array $items آیتم‌ها (title, url)
 * @return string خروجی HTML
 */
function generateBreadcrumb($items) {
    $html = '<nav aria-label="breadcrumb">
        <ol class="breadcrumb">';
    
    foreach ($items as $i => $item) {
        if ($i === count($items) - 1) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . $item['title'] . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="' . $item['url'] . '">' . $item['title'] . '</a></li>';
        }
    }
    
    $html .= '</ol></nav>';
    
    return $html;
}

/**
 * نمایش پیام flash
 * 
 * @param string $type نوع پیام (success|error|warning|info)
 * @param string $message متن پیام
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message,
        'time' => time()
    ];
}

/**
 * دریافت پیام flash
 * 
 * @return string|null خروجی HTML
 */
function getFlashMessage() {
    if (!isset($_SESSION['flash_message'])) {
        return null;
    }
    
    $flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
    
    // بررسی انقضای پیام
    if (time() - $flash['time'] > 5) {
        return null;
    }
    
    $classes = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    $icons = [
        'success' => 'fas fa-check-circle',
        'error' => 'fas fa-exclamation-circle',
        'warning' => 'fas fa-exclamation-triangle',
        'info' => 'fas fa-info-circle'
    ];
    
    return sprintf(
        '<div class="alert %s alert-dismissible fade show" role="alert">
            <i class="%s me-2"></i>
            %s
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>',
        $classes[$flash['type']],
        $icons[$flash['type']],
        $flash['message']
    );
}

/**
 * بررسی درخواست POST
 * 
 * @return bool
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * بررسی درخواست AJAX
 * 
 * @return bool
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * تولید پاسخ JSON
 * 
 * @param mixed $data داده‌ها
 * @param int $status کد وضعیت
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * ریدایرکت
 * 
 * @param string $url آدرس مقصد
 */
function redirect($url) {
    header('Location: ' . url($url));
    exit;
}

/**
 * بررسی وجود فایل تصویری
 * 
 * @param string $path مسیر فایل
 * @return string مسیر فایل یا تصویر پیش‌فرض
 */
function checkImage($path) {
    if (file_exists(BASEPATH . '/public/' . $path)) {
        return asset($path);
    }
    
    return asset('images/no-image.png');
}

/**
 * تولید دکمه حذف با تأیید
 * 
 * @param string $url آدرس حذف
 * @param string $title متن تأیید
 * @param string $message پیام تأیید
 * @return string خروجی HTML
 */
function deleteButton($url, $title = 'حذف', $message = 'آیا از حذف این مورد اطمینان دارید؟') {
    return sprintf(
        '<form method="post" action="%s" class="d-inline delete-form">
            <input type="hidden" name="csrf_token" value="%s">
            <button type="submit" class="btn btn-danger btn-sm" data-confirm="%s">
                <i class="fas fa-trash-alt"></i> %s
            </button>
        </form>',
        url($url),
        generateCsrfToken(),
        $message,
        $title
    );
}

/**
 * تولید دکمه ویرایش
 * 
 * @param string $url آدرس ویرایش
 * @param string $title متن دکمه
 * @return string خروجی HTML
 */
function editButton($url, $title = 'ویرایش') {
    return sprintf(
        '<a href="%s" class="btn btn-primary btn-sm">
            <i class="fas fa-edit"></i> %s
        </a>',
        url($url),
        $title
    );
}

/**
 * تبدیل آرایه به المان‌های select
 * 
 * @param array $items آیتم‌ها
 * @param mixed $selected مقدار انتخاب شده
 * @return string خروجی HTML
 */
function arrayToSelect($items, $selected = null) {
    $html = '';
    
    foreach ($items as $value => $label) {
        $html .= sprintf(
            '<option value="%s"%s>%s</option>',
            $value,
            $selected == $value ? ' selected' : '',
            $label
        );
    }
    
    return $html;
}

/**
 * تولید کد تأیید
 * 
 * @param int $length طول کد
 * @return string کد تأیید
 */
function generateVerificationCode($length = 6) {
    return generateRandomString($length, 'numeric');
}

/**
 * بررسی کد ملی
 * 
 * @param string $code کد ملی
 * @return bool
 */
function validateNationalCode($code) {
    if (!preg_match('/^[0-9]{10}$/', $code)) {
        return false;
    }
    
    for ($i = 0; $i < 10; $i++) {
        if (preg_match('/^' . $i . '{10}$/', $code)) {
            return false;
        }
    }
    
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += ((10 - $i) * intval(substr($code, $i, 1)));
    }
    
    $ret = $sum % 11;
    $parity = intval(substr($code, 9, 1));
    
    if ($ret < 2) {
        return $ret == $parity;
    }
    
    return $ret == 11 - $parity;
}

/**
 * اعتبارسنجی شماره موبایل
 * 
 * @param string $mobile شماره موبایل
 * @return bool
 */
function validateMobile($mobile) {
    return preg_match('/^09[0-9]{9}$/', $mobile);
}

/**
 * مرتب‌سازی آرایه براساس کلید فارسی
 * 
 * @param array $array آرایه ورودی
 * @return array آرایه مرتب شده
 */
function sortFarsi($array) {
    $alphabet = [
        'آ', 'ا', 'ب', 'پ', 'ت', 'ث', 'ج', 'چ', 'ح', 'خ',
        'د', 'ذ', 'ر', 'ز', 'ژ', 'س', 'ش', 'ص', 'ض', 'ط',
        'ظ', 'ع', 'غ', 'ف', 'ق', 'ک', 'گ', 'ل', 'م', 'ن',
        'و', 'ه', 'ی', 'ي'
    ];
    
    usort($array, function($a, $b) use ($alphabet) {
        $a = mb_substr($a, 0, 1);
        $b = mb_substr($b, 0, 1);
        
        $aIndex = array_search($a, $alphabet);
        $bIndex = array_search($b, $alphabet);
        
        if ($aIndex === false) return 1;
        if ($bIndex === false) return -1;
        
        return $aIndex - $bIndex;
    });
    
    return $array;
}

/**
 * بررسی تاریخ شمسی
 * 
 * @param string $date تاریخ شمسی
 * @return bool
 */
function validateJalaliDate($date) {
    if (!preg_match('/^[0-9]{4}\/(?:0[1-9]|1[0-2])\/(?:0[1-9]|[12][0-9]|3[01])$/', $date)) {
        return false;
    }
    
    list($year, $month, $day) = explode('/', $date);
    
    return jcheckdate($month, $day, $year);
}

/**
 * محاسبه فاصله زمانی نسبی
 * 
 * @param string $datetime تاریخ و زمان
 * @return string متن فاصله زمانی
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    $intervals = [
        31536000 => 'سال',
        2592000 => 'ماه',
        604800 => 'هفته',
        86400 => 'روز',
        3600 => 'ساعت',
        60 => 'دقیقه',
        1 => 'ثانیه'
    ];
    
    foreach ($intervals as $seconds => $unit) {
        $interval = floor($diff / $seconds);
        
        if ($interval > 0) {
            return toFa($interval) . ' ' . $unit . ' پیش';
        }
    }
    
    return 'لحظاتی پیش';
}

/**
 * گرفتن مقدار از آرایه
 * 
 * @param array $array آرایه
 * @param string $key کلید
 * @param mixed $default مقدار پیش‌فرض
 * @return mixed مقدار
 */
function arrayGet($array, $key, $default = null) {
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * تبدیل قیمت به حروف
 * 
 * @param int $price قیمت
 * @return string قیمت به حروف
 */
function priceToWords($price) {
    $units = ['', 'هزار', 'میلیون', 'میلیارد', 'تریلیون'];
    $words = [
        0 => 'صفر',
        1 => 'یک',
        2 => 'دو',
        3 => 'سه',
        4 => 'چهار',
        5 => 'پنج',
        6 => 'شش',
        7 => 'هفت',
        8 => 'هشت',
        9 => 'نه',
        10 => 'ده',
        11 => 'یازده',
        12 => 'دوازده',
        13 => 'سیزده',
        14 => 'چهارده',
        15 => 'پانزده',
        16 => 'شانزده',
        17 => 'هفده',
        18 => 'هجده',
        19 => 'نوزده',
        20 => 'بیست',
        30 => 'سی',
        40 => 'چهل',
        50 => 'پنجاه',
        60 => 'شصت',
        70 => 'هفتاد',
        80 => 'هشتاد',
        90 => 'نود',
        100 => 'صد',
        200 => 'دویست',
        300 => 'سیصد',
        400 => 'چهارصد',
        500 => 'پانصد',
        600 => 'ششصد',
        700 => 'هفتصد',
        800 => 'هشتصد',
        900 => 'نهصد'
    ];
    
    if ($price === 0) {
        return $words[0];
    }
    
    $result = [];
    $unit = 0;
    
    while ($price > 0) {
        $section = $price % 1000;
        
        if ($section > 0) {
            $sectionWords = [];
            
            // صدگان
            $hundreds = floor($section / 100) * 100;
            if ($hundreds > 0) {
                $sectionWords[] = $words[$hundreds];
            }
            
            // دهگان و یکان
            $remainder = $section % 100;
            if ($remainder > 0) {
                if ($remainder < 20) {
                    $sectionWords[] = $words[$remainder];
                } else {
                    $tens = floor($remainder / 10) * 10;
                    $ones = $remainder % 10;
                    
                    if ($tens > 0) {
                        $sectionWords[] = $words[$tens];
                    }
                    
                    if ($ones > 0) {
                        $sectionWords[] = $words[$ones];
                    }
                }
            }
            
            $sectionText = implode(' و ', $sectionWords);
            if ($unit > 0) {
                $sectionText .= ' ' . $units[$unit];
            }
            
            $result[] = $sectionText;
        }
        
        $price = floor($price / 1000);
        $unit++;
    }
    
    return implode(' و ', array_reverse($result)) . ' تومان';
}

/**
 * بررسی وجود مقدار در آرایه با in_array سازگار با UTF-8
 * 
 * @param string $needle مقدار مورد جستجو
 * @param array $haystack آرایه
 * @param bool $strict مقایسه دقیق
 * @return bool
 */
function mb_in_array($needle, $haystack, $strict = false) {
    return in_array($needle, array_map('mb_strtolower', $haystack), $strict);
}

/**
 * مرتب‌سازی آرایه براساس یک کلید با پشتیبانی از UTF-8
 * 
 * @param array $array آرایه
 * @param string $key نام کلید
 * @return array آرایه مرتب شده
 */
function mb_array_sort($array, $key) {
    usort($array, function($a, $b) use ($key) {
        return mb_strcoll($a[$key], $b[$key]);
    });
    
    return $array;
}

/**
 * تابع برای مقایسه نسخه‌های نرم‌افزار
 * 
 * @param string $version1 نسخه اول
 * @param string $version2 نسخه دوم
 * @return int -1 اگر نسخه اول کوچکتر، 0 اگر برابر و 1 اگر بزرگتر باشد
 */
function compareVersions($version1, $version2) {
    $v1 = explode('.', $version1);
    $v2 = explode('.', $version2);
    
    $len = max(count($v1), count($v2));
    
    for ($i = 0; $i < $len; $i++) {
        $num1 = isset($v1[$i]) ? intval($v1[$i]) : 0;
        $num2 = isset($v2[$i]) ? intval($v2[$i]) : 0;
        
        if ($num1 < $num2) return -1;
        if ($num1 > $num2) return 1;
    }
    
    return 0;
}

/**
 * تولید کد رهگیری یکتا
 * 
 * @param string $prefix پیشوند کد
 * @return string کد رهگیری
 */
function generateTrackingCode($prefix = '') {
    $date = date('Ymd');
    $random = strtoupper(substr(uniqid(), -4));
    $number = mt_rand(1000, 9999);
    
    return $prefix . $date . $random . $number;
}

/**
 * محاسبه درصد تخفیف
 * 
 * @param float $originalPrice قیمت اصلی
 * @param float $discountedPrice قیمت با تخفیف
 * @return int درصد تخفیف
 */
function calculateDiscountPercent($originalPrice, $discountedPrice) {
    if ($originalPrice <= 0) return 0;
    $discount = $originalPrice - $discountedPrice;
    return round(($discount / $originalPrice) * 100);
}

/**
 * تبدیل تاریخ میلادی به روز هفته فارسی
 * 
 * @param string $date تاریخ میلادی
 * @return string نام روز هفته
 */
function getDayName($date) {
    $timestamp = strtotime($date);
    $dayNum = date('w', $timestamp);
    
    $days = [
        'یکشنبه',
        'دوشنبه',
        'سه‌شنبه',
        'چهارشنبه',
        'پنجشنبه',
        'جمعه',
        'شنبه'
    ];
    
    return $days[$dayNum];
}

/**
 * تبدیل تاریخ میلادی به نام ماه فارسی
 * 
 * @param string $date تاریخ میلادی
 * @return string نام ماه
 */
function getMonthName($date) {
    $jalaliDate = toJalali($date);
    $month = intval(explode('/', $jalaliDate)[1]);
    
    $months = [
        1 => 'فروردین',
        2 => 'اردیبهشت',
        3 => 'خرداد',
        4 => 'تیر',
        5 => 'مرداد',
        6 => 'شهریور',
        7 => 'مهر',
        8 => 'آبان',
        9 => 'آذر',
        10 => 'دی',
        11 => 'بهمن',
        12 => 'اسفند'
    ];
    
    return $months[$month];
}

/**
 * تبدیل عدد به فرمت واحد پول
 * 
 * @param float $amount مبلغ
 * @param string $currency واحد پول
 * @return string مبلغ فرمت شده
 */
function formatCurrency($amount, $currency = 'تومان') {
    return formatNumber($amount) . ' ' . $currency;
}

/**
 * تولید کد سفارش یکتا
 * 
 * @return string کد سفارش
 */
function generateOrderCode() {
    $prefix = 'ORD';
    $date = date('ymd');
    $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
    return $prefix . $date . $random;
}

/**
 * ایجاد QR Code
 * 
 * @param string $data اطلاعات
 * @param int $size سایز تصویر
 * @return string تصویر QR Code در قالب base64
 */
function generateQRCode($data, $size = 200) {
    require_once BASEPATH . '/includes/phpqrcode/qrlib.php';
    
    $tempFile = tempnam(sys_get_temp_dir(), 'qr_');
    QRcode::png($data, $tempFile, QR_ECLEVEL_L, 10, 2);
    
    $qrCode = base64_encode(file_get_contents($tempFile));
    unlink($tempFile);
    
    return 'data:image/png;base64,' . $qrCode;
}

/**
 * بررسی دسترسی کاربر به یک عملیات
 * 
 * @param string $permission دسترسی مورد نظر
 * @return bool
 */
function hasPermission($permission) {
    if (!isset($_SESSION['user_permissions'])) {
        return false;
    }
    
    return in_array($permission, $_SESSION['user_permissions']);
}

/**
 * محاسبه مجموع مالیات
 * 
 * @param float $amount مبلغ
 * @param float $vatRate درصد مالیات بر ارزش افزوده
 * @return float مبلغ مالیات
 */
function calculateVAT($amount, $vatRate = 9) {
    return round($amount * ($vatRate / 100));
}

/**
 * تولید شماره فاکتور
 * 
 * @param string $prefix پیشوند
 * @return string شماره فاکتور
 */
function generateInvoiceNumber($prefix = 'INV') {
    return $prefix . date('Ym') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * تبدیل آرایه به CSV
 * 
 * @param array $data داده‌ها
 * @param array $header سرستون‌ها
 * @return string فایل CSV
 */
function arrayToCSV($data, $header = []) {
    $output = fopen('php://temp', 'r+');
    
    // افزودن BOM برای پشتیبانی از کاراکترهای یونیکد
    fputs($output, "\xEF\xBB\xBF");
    
    // نوشتن سرستون‌ها
    if (!empty($header)) {
        fputcsv($output, $header);
    }
    
    // نوشتن داده‌ها
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    
    return $csv;
}

/**
 * تبدیل تاریخ به فرمت RSS
 * 
 * @param string $date تاریخ
 * @return string تاریخ در فرمت RSS
 */
function formatRSSDate($date) {
    return date('D, d M Y H:i:s O', strtotime($date));
}

/**
 * تولید نام مستعار یکتا
 * 
 * @param string $title عنوان
 * @param string $table نام جدول
 * @param string $field نام فیلد
 * @return string نام مستعار یکتا
 */
function generateUniqueSlug($title, $table, $field = 'slug') {
    $slug = generateSlug($title);
    $db = Database::getInstance();
    
    $i = 1;
    $newSlug = $slug;
    
    while ($db->getValue("SELECT COUNT(*) FROM " . DB_PREFIX . $table . " WHERE $field = ?", [$newSlug]) > 0) {
        $newSlug = $slug . '-' . $i;
        $i++;
    }
    
    return $newSlug;
}

/**
 * ایجاد فایل کش
 * 
 * @param string $key کلید کش
 * @param mixed $data داده
 * @param int $ttl زمان انقضا به ثانیه
 */
function setCache($key, $data, $ttl = 3600) {
    $cacheFile = BASEPATH . '/cache/' . md5($key) . '.cache';
    $cacheData = [
        'expires' => time() + $ttl,
        'data' => $data
    ];
    
    file_put_contents($cacheFile, serialize($cacheData));
}

/**
 * دریافت داده از کش
 * 
 * @param string $key کلید کش
 * @return mixed|null داده یا null در صورت عدم وجود
 */
function getCache($key) {
    $cacheFile = BASEPATH . '/cache/' . md5($key) . '.cache';
    
    if (!file_exists($cacheFile)) {
        return null;
    }
    
    $cacheData = unserialize(file_get_contents($cacheFile));
    
    if (time() > $cacheData['expires']) {
        unlink($cacheFile);
        return null;
    }
    
    return $cacheData['data'];
}

/**
 * پاک کردن کش
 * 
 * @param string $key کلید کش (اختیاری - برای پاک کردن همه)
 */
function clearCache($key = null) {
    if ($key === null) {
        array_map('unlink', glob(BASEPATH . '/cache/*.cache'));
    } else {
        $cacheFile = BASEPATH . '/cache/' . md5($key) . '.cache';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
}

/**
 * لاگ کردن خطاها
 * 
 * @param string $message پیام خطا
 * @param string $type نوع خطا
 * @param array $context اطلاعات اضافی
 */
function logError($message, $type = 'ERROR', $context = []) {
    $logFile = BASEPATH . '/logs/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    
    $logData = [
        'timestamp' => $timestamp,
        'type' => $type,
        'message' => $message,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'url' => $_SERVER['REQUEST_URI'],
        'context' => $context
    ];
    
    $logMessage = json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * بررسی اعتبار کد پستی
 * 
 * @param string $postalCode کد پستی
 * @return bool
 */
function validatePostalCode($postalCode) {
    return preg_match('/^[0-9]{10}$/', $postalCode);
}

/**
 * تبدیل حروف عربی به فارسی
 * 
 * @param string $text متن ورودی
 * @return string متن تبدیل شده
 */
function arabicToPersian($text) {
    $arabic = ['ي', 'ك', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '٠'];
    $persian = ['ی', 'ک', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '۰'];
    return str_replace($arabic, $persian, $text);
}

/**
 * محاسبه سن از تاریخ تولد
 * 
 * @param string $birthdate تاریخ تولد (Y-m-d)
 * @return int سن
 */
function calculateAge($birthdate) {
    $birth = new DateTime($birthdate);
    $today = new DateTime();
    $diff = $today->diff($birth);
    return $diff->y;
}

/**
 * دریافت IP واقعی کاربر
 * 
 * @return string آدرس IP
 */
function getRealIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * تبدیل حروف فارسی به انگلیسی
 * 
 * @param string $text متن ورودی
 * @return string متن تبدیل شده
 */
function persianToEnglish($text) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($persian, $english, $text);
}

/**
 * تولید رمز عبور تصادفی
 * 
 * @param int $length طول رمز عبور
 * @return string رمز عبور
 */
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
    $password = '';
    
    // حداقل یک حرف کوچک
    $password .= $chars[random_int(0, 25)];
    
    // حداقل یک حرف بزرگ
    $password .= $chars[random_int(26, 51)];
    
    // حداقل یک عدد
    $password .= $chars[random_int(52, 61)];
    
    // حداقل یک کاراکتر خاص
    $password .= $chars[random_int(62, strlen($chars) - 1)];
    
    // تکمیل طول رمز عبور
    for ($i = strlen($password); $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    // مخلوط کردن کاراکترها
    return str_shuffle($password);
}

/**
 * فرمت کردن شماره کارت بانکی
 * 
 * @param string $cardNumber شماره کارت
 * @return string شماره کارت فرمت شده
 */
function formatCardNumber($cardNumber) {
    $number = preg_replace('/[^0-9]/', '', $cardNumber);
    return implode('-', str_split($number, 4));
}

/**
 * بررسی اعتبار شماره کارت بانکی
 * 
 * @param string $cardNumber شماره کارت
 * @return bool
 */
function validateCardNumber($cardNumber) {
    $card = preg_replace('/\D/', '', $cardNumber);
    
    if (strlen($card) !== 16) {
        return false;
    }
    
    $sum = 0;
    for ($i = 0; $i < 16; $i++) {
        $num = intval($card[$i]);
        if ($i % 2 === 0) {
            $num *= 2;
            if ($num > 9) {
                $num -= 9;
            }
        }
        $sum += $num;
    }
    
    return $sum % 10 === 0;
}

/**
 * بررسی اعتبار شماره شبا
 * 
 * @param string $iban شماره شبا
 * @return bool
 */
function validateIBAN($iban) {
    $iban = strtoupper(str_replace(' ', '', $iban));
    
    if (!preg_match('/^IR[0-9]{24}$/', $iban)) {
        return false;
    }
    
    $iban = substr($iban, 4) . '1827' . substr($iban, 2, 2);
    
    $remainder = '';
    for ($i = 0; $i < strlen($iban); $i += 7) {
        $part = $remainder . substr($iban, $i, 7);
        $remainder = $part % 97;
    }
    
    return $remainder === 1;
}

/**
 * تبدیل تاریخ شمسی به قمری
 * 
 * @param string $date تاریخ شمسی (Y/m/d)
 * @return string تاریخ قمری
 */
function solarToLunar($date) {
    require_once BASEPATH . '/includes/jdf.php';
    
    $date = toEn($date);
    list($year, $month, $day) = explode('/', $date);
    
    $gregorian = jalali_to_gregorian($year, $month, $day);
    $timestamp = mktime(0, 0, 0, $gregorian[1], $gregorian[2], $gregorian[0]);
    
    return toFa(jdate('Y/m/d', $timestamp, '', '', 'en'));
}

/**
 * محاسبه BMI (شاخص توده بدنی)
 * 
 * @param float $weight وزن به کیلوگرم
 * @param float $height قد به متر
 * @return array شاخص و وضعیت
 */
function calculateBMI($weight, $height) {
    $bmi = $weight / ($height * $height);
    
    if ($bmi < 18.5) {
        $status = 'کمبود وزن';
    } elseif ($bmi < 25) {
        $status = 'وزن نرمال';
    } elseif ($bmi < 30) {
        $status = 'اضافه وزن';
    } else {
        $status = 'چاقی';
    }
    
    return [
        'bmi' => round($bmi, 1),
        'status' => $status
    ];
}

/**
 * تشخیص نوع فایل از محتوا
 * 
 * @param string $content محتوای فایل
 * @return string|null نوع MIME یا null
 */
function detectMimeType($content) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    return $finfo->buffer($content);
}

// پایان فایل functions.php