<?php
/**
 * صفحه اصلی
 * 
 * @package HesabinoAccounting
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// دریافت نمونه کلاس Auth
$auth = Auth::getInstance();

// اگر کاربر لاگین است به داشبورد هدایت شود
if ($auth->isLoggedIn()) {
    redirect('dashboard');
}

// عنوان صفحه
$pageTitle = 'خانه';
?>

<!-- بخش هدر -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6" data-aos="fade-right">
                <h1 class="display-4 fw-bold mb-4">مدیریت هوشمند امور مالی کسب و کار شما</h1>
                <p class="lead mb-4">
                    با سیستم حسابداری <?php echo SITE_NAME; ?>، به راحتی امور مالی کسب و کار خود را مدیریت کنید.
                    صدور فاکتور، مدیریت مشتریان، کنترل موجودی و گزارش‌های متنوع در یک نرم‌افزار جامع.
                </p>
                <div class="d-flex gap-3">
                    <a href="register" class="btn btn-light">
                        <i class="fas fa-rocket me-1"></i>
                        شروع رایگان
                    </a>
                    <a href="#features" class="btn btn-outline-light">
                        <i class="fas fa-info-circle me-1"></i>
                        اطلاعات بیشتر
                    </a>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <img src="<?php echo asset('images/hero-image.png'); ?>" alt="حسابداری آنلاین" class="img-fluid floating">
            </div>
        </div>
    </div>
</section>

<!-- بخش ویژگی‌ها -->
<section id="features" class="features-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">ویژگی‌های سیستم</h2>
            <p class="text-muted">امکانات پیشرفته برای مدیریت بهتر کسب و کار شما</p>
        </div>
        <div class="row g-4">
            <!-- ویژگی 1 -->
            <div class="col-lg-4 col-md-6" data-aos="fade-up">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <h4>صدور فاکتور</h4>
                        <p>صدور آسان و سریع فاکتور با قالب‌های متنوع و امکان ارسال مستقیم به مشتری</p>
                    </div>
                </div>
            </div>
            
            <!-- ویژگی 2 -->
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4>گزارش‌های مالی</h4>
                        <p>گزارش‌های متنوع و کاربردی برای تحلیل عملکرد مالی کسب و کار</p>
                    </div>
                </div>
            </div>
            
            <!-- ویژگی 3 -->
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <h4>مدیریت موجودی</h4>
                        <p>کنترل موجودی انبار و محصولات با سیستم هشدار خودکار</p>
                    </div>
                </div>
            </div>
            
            <!-- ویژگی 4 -->
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>مدیریت مشتریان</h4>
                        <p>ثبت و پیگیری اطلاعات مشتریان و سوابق خرید آنها</p>
                    </div>
                </div>
            </div>
            
            <!-- ویژگی 5 -->
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h4>حسابداری دوبل</h4>
                        <p>سیستم حسابداری دوطرفه با ثبت خودکار سند حسابداری</p>
                    </div>
                </div>
            </div>
            
            <!-- ویژگی 6 -->
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4>نسخه موبایل</h4>
                        <p>دسترسی به سیستم از طریق گوشی موبایل و تبلت</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- بخش قیمت‌گذاری -->
<section id="pricing" class="pricing-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">تعرفه‌های اشتراک</h2>
            <p class="text-muted">پلن متناسب با نیاز خود را انتخاب کنید</p>
        </div>
        <div class="row g-4">
            <!-- پلن پایه -->
            <div class="col-lg-4" data-aos="fade-up">
                <div class="card price-card h-100">
                    <div class="card-body text-center">
                        <h3 class="card-title">پایه</h3>
                        <div class="py-4">
                            <h1 class="display-4 fw-bold">رایگان</h1>
                            <p class="text-muted">برای همیشه</p>
                        </div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>صدور 10 فاکتور در ماه</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>گزارش‌های پایه</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>پشتیبانی ایمیلی</li>
                            <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>حسابداری دوبل</li>
                            <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>اپلیکیشن موبایل</li>
                        </ul>
                        <a href="register" class="btn btn-outline-primary w-100">شروع کنید</a>
                    </div>
                </div>
            </div>

            <!-- پلن حرفه‌ای -->
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card price-card popular h-100">
                    <div class="card-body text-center">
                        <h3 class="card-title">حرفه‌ای</h3>
                        <div class="py-4">
                            <h1 class="display-4 fw-bold">199,000</h1>
                            <p class="text-muted">تومان / ماهانه</p>
                        </div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>صدور نامحدود فاکتور</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>تمام گزارش‌ها</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>پشتیبانی تلفنی</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>حسابداری دوبل</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>اپلیکیشن موبایل</li>
                        </ul>
                        <a href="register" class="btn btn-primary w-100">انتخاب پلن</a>
                    </div>
                </div>
            </div>

            <!-- پلن سازمانی -->
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card price-card h-100">
                    <div class="card-body text-center">
                        <h3 class="card-title">سازمانی</h3>
                        <div class="py-4">
                            <h1 class="display-4 fw-bold">تماس بگیرید</h1>
                            <p class="text-muted">قیمت توافقی</p>
                        </div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>تمام امکانات حرفه‌ای</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>شخصی‌سازی کامل</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>پشتیبانی اختصاصی</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>آموزش حضوری</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>نصب در شبکه داخلی</li>
                        </ul>
                        <a href="#contact" class="btn btn-outline-primary w-100">تماس با ما</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- بخش نظرات مشتریان -->
<section id="testimonials" class="testimonials-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">نظرات مشتریان</h2>
            <p class="text-muted">آنچه مشتریان ما درباره <?php echo SITE_NAME; ?> می‌گویند</p>
        </div>
        <div class="swiper testimonials-slider">
            <div class="swiper-wrapper">
                <!-- نظر 1 -->
                <div class="swiper-slide">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo asset('images/client1.jpg'); ?>" alt="Client 1" class="client-image me-3">
                            <div>
                                <h5 class="mb-1">علی محمدی</h5>
                                <p class="text-muted mb-0">مدیر فروشگاه زنجیره‌ای</p>
                            </div>
                        </div>
                        <p class="mb-0">استفاده از این سیستم حسابداری باعث شد مدیریت مالی فروشگاه‌های ما بسیار راحت‌تر شود.</p>
                    </div>
                </div>

                <!-- نظر 2 -->
                <div class="swiper-slide">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo asset('images/client2.jpg'); ?>" alt="Client 2" class="client-image me-3">
                            <div>
                                <h5 class="mb-1">مریم حسینی</h5>
                                <p class="text-muted mb-0">صاحب استارتاپ</p>
                            </div>
                        </div>
                        <p class="mb-0">رابط کاربری ساده و امکانات کامل این نرم‌افزار کمک زیادی به رشد کسب و کار ما کرد.</p>
                    </div>
                </div>

                <!-- نظر 3 -->
                <div class="swiper-slide">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo asset('images/client3.jpg'); ?>" alt="Client 3" class="client-image me-3">
                            <div>
                                <h5 class="mb-1">رضا کریمی</h5>
                                <p class="text-muted mb-0">حسابدار شرکت</p>
                            </div>
                        </div>
                        <p class="mb-0">به عنوان یک حسابدار حرفه‌ای، این بهترین نرم‌افزاری است که تا به حال استفاده کرده‌ام.</p>
                    </div>
                </div>
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </div>
</section>

<!-- بخش تماس -->
<section id="contact" class="contact-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h2 class="fw-bold mb-4">تماس با ما</h2>
                <p class="mb-4">برای دریافت مشاوره رایگان و اطلاعات بیشتر با ما در تماس باشید.</p>
                <div class="d-flex mb-3">
                    <i class="fas fa-map-marker-alt fa-2x me-3"></i>
                    <div>
                        <h5>آدرس</h5>
                        <p>تهران، خیابان ولیعصر، ساختمان نور، طبقه 4</p>
                    </div>
                </div>
                <div class="d-flex mb-3">
                    <i class="fas fa-phone fa-2x me-3"></i>
                    <div>
                        <h5>تلفن تماس</h5>
                        <p>021-12345678</p>
                    </div>
                </div>
                <div class="d-flex">
                    <i class="fas fa-envelope fa-2x me-3"></i>
                    <div>
                        <h5>ایمیل</h5>
                        <p>info@hesabino.com</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="contact-form">
                    <form id="contactForm">
                        <div class="mb-3">
                            <label for="name" class="form-label text-dark">نام و نام خانوادگی</label>
                            <input type="text" class="form-control" id="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label text-dark">ایمیل</label>
                            <input type="email" class="form-control" id="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label text-dark">موضوع</label>
                            <input type="text" class="form-control" id="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label text-dark">پیام</label>
                            <textarea class="form-control" id="message" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane me-2"></i>ارسال پیام
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>