#!/bin/bash  

# ساخت دایرکتوری‌ها  
mkdir -p assets/css  
mkdir -p assets/js  
mkdir -p assets/images  

mkdir -p includes  
mkdir -p pages/dashboard  
mkdir -p pages/products  
mkdir -p pages/invoices  
mkdir -p pages/inventory  

# ایجاد فایل‌ها در includes  
touch includes/config.php  
touch includes/functions.php  
touch includes/database.php  
touch includes/header.php  
touch includes/footer.php  

# فایل اصلی در ریشه پروژه  
touch index.php  

echo "ساختار پوشه‌ها و فایل‌های پروژه ساخته شد."  