<?php
/**
 * قالب فوتر
 * 
 * @package HesabinoAccounting
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
?>
    </main>

    <!-- فوتر -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <img src="<?php echo asset('images/logo-light.png'); ?>" alt="<?php echo SITE_NAME; ?>" class="mb-4" style="height: 40px;">
                    <p>سیستم حسابداری آنلاین برای مدیریت هوشمند امور مالی کسب و کارها</p>
                    <div class="mt-4">
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-telegram fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-linkedin fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="text-white mb-4">دسترسی سریع</h5>
                    <ul class="list-unstyled footer-links">
                        <li><a href="<?php echo url(); ?>">صفحه اصلی</a></li>
                        <li><a href="<?php echo url('about'); ?>">درباره ما</a></li>
                        <li><a href="<?php echo url('contact'); ?>">تماس با ما</a></li>
                        <li><a href="<?php echo url('blog'); ?>">وبلاگ</a></li>
                        <li><a href="<?php echo url('pricing'); ?>">تعرفه‌ها</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="text-white mb-4">خدمات</h5>
                    <ul class="list-unstyled footer-links">
                        <li><a href="<?php echo url('invoicing'); ?>">صدور فاکتور</a></li>
                        <li><a href="<?php echo url('accounting'); ?>">حسابداری</a></li>
                        <li><a href="<?php echo url('inventory'); ?>">انبارداری</a></li>
                        <li><a href="<?php echo url('reports'); ?>">گزارش‌گیری</a></li>
                        <li><a href="<?php echo url('api'); ?>">API</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-6">
                    <h5 class="text-white mb-4">خبرنامه</h5>
                    <p>برای اطلاع از آخرین اخبار و بروزرسانی‌ها در خبرنامه ما عضو شوید</p>
                    <form class="newsletter-form mt-4" id="newsletterForm">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="ایمیل خود را وارد کنید" required>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <hr class="mt-5 mb-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. تمامی حقوق محفوظ است.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item">
                            <a href="<?php echo url('terms'); ?>">قوانین و مقررات</a>
                        </li>
                        <li class="list-inline-item">
                            <a href="<?php echo url('privacy'); ?>">حریم خصوصی</a>
                        </li>
                        <li class="list-inline-item">
                            <a href="<?php echo url('faq'); ?>">سوالات متداول</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top -->
    <a href="#" class="back-to-top" id="backToTop">
        <i class="fas fa-chevron-up"></i>
    </a>

    <!-- اسکریپت‌های ضروری -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    
    <!-- اسکریپت‌های سفارشی -->
    <script src="<?php echo asset('js/app.js'); ?>"></script>
    
    <!-- اسکریپت‌های اختصاصی صفحه -->
    <?php if (isset($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?php echo asset('js/' . $script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        // تنظیمات AOS
        AOS.init({
            duration: 800,
            once: true
        });

        // نمایش دکمه برگشت به بالا
        const backToTop = document.getElementById('backToTop');
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 100) {
                backToTop.classList.add('show');
            } else {
                backToTop.classList.remove('show');
            }
        });

        // اسکرول نرم به بالای صفحه
        backToTop.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // فرم خبرنامه
        const newsletterForm = document.getElementById('newsletterForm');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const email = newsletterForm.querySelector('input[type="email"]').value;
                
                // ارسال ایمیل به سرور
                fetch('<?php echo url("api/newsletter/subscribe"); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'تبریک!',
                            text: 'ایمیل شما با موفقیت در خبرنامه ثبت شد.',
                            confirmButtonText: 'باشه'
                        });
                        newsletterForm.reset();
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا!',
                        text: error.message || 'مشکلی در ثبت ایمیل پیش آمد. لطفاً دوباره تلاش کنید.',
                        confirmButtonText: 'باشه'
                    });
                });
            });
        }

        // تأیید حذف با SweetAlert2
        document.querySelectorAll('.delete-form').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const message = form.querySelector('button[type="submit"]').dataset.confirm;
                
                Swal.fire({
                    title: 'آیا مطمئن هستید؟',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'بله، حذف شود',
                    cancelButtonText: 'انصراف'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
</body>
</html>