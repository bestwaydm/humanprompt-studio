# خطوات رفع HumanPrompt Studio على GitHub

هذا الملف يشرح رفع المشروع إلى GitHub بدون رفع مفاتيح API أو ملف `.env`.

## 1. افحص الملفات قبل الرفع

تأكد أن هذه الملفات غير موجودة داخل النسخة التي سترفعها:

```text
.env
data/ai_providers.json
data/prompt_history.json
```

هذه الملفات تحتوي إعدادات خاصة أو مفاتيح أو سجل استخدام، ويجب ألا ترفعها.

## 2. أنشئ Repository جديد على GitHub

1. افتح GitHub.
2. اضغط **New repository**.
3. اكتب الاسم مثل:

```text
humanprompt-studio
```

4. اختر Public أو Private.
5. لا تضف README من GitHub لأن المشروع يحتوي README جاهز.
6. اضغط **Create repository**.

## 3. طريقة الرفع عبر GitHub Desktop

1. افتح GitHub Desktop.
2. اختر **File > Add local repository**.
3. اختر مجلد المشروع.
4. إذا طلب إنشاء Git repository اضغط Create.
5. اكتب رسالة commit:

```text
Initial release: HumanPrompt Studio v1.0
```

6. اضغط **Commit to main**.
7. اضغط **Publish repository**.
8. اختر Public أو Private.
9. اضغط Publish.

## 4. طريقة الرفع عبر Git CLI

افتح Terminal داخل مجلد المشروع واكتب:

```bash
git init
git add .
git commit -m "Initial release: HumanPrompt Studio v1.0"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/humanprompt-studio.git
git push -u origin main
```

استبدل:

```text
YOUR_USERNAME
```

باسم حسابك في GitHub.

## 5. بعد الرفع

افتح صفحة المستودع وتأكد من وجود الملفات التالية:

```text
README.md
LICENSE
SECURITY.md
CONTRIBUTING.md
CHANGELOG.md
docs/
app/
public/
config/
data/
storage/
```

وتأكد من عدم وجود:

```text
.env
data/ai_providers.json
data/prompt_history.json
```

## 6. تشغيل المشروع بعد تنزيله من GitHub

أي شخص يريد تشغيل المشروع يفعل التالي:

```bash
git clone https://github.com/YOUR_USERNAME/humanprompt-studio.git
cd humanprompt-studio
cp .env.example .env
php -S localhost:8000 -t public
```

ثم يفتح:

```text
http://localhost:8000
```

ولإضافة مفاتيح API:

```text
http://localhost:8000/providers.php
```

## 7. أمر فحص سريع قبل الرفع

على Linux/macOS/Git Bash:

```bash
grep -R "sk-\|api_key\|HUMANPROMPT_SECRET_KEY=.*[A-Za-z0-9]" . --exclude-dir=.git
```

إذا ظهر مفتاح حقيقي أو رقم خاص أو secret حقيقي، احذفه قبل الرفع.
