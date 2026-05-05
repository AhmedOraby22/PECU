<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\CatalogItem;
use App\Models\HeroSlide;
use App\Models\Product;
use App\Models\Quote;
use App\Models\SitePage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LegacyApiController extends Controller
{
    public function handle(Request $request)
    {
        if ($request->isMethod('options')) {
            return response('', 204);
        }

        $action = (string) ($request->query('action') ?? '');
        $body = [];
        if ($request->isMethod('post')) {
            $raw = $request->getContent();
            if (!is_string($raw) || $raw === '') {
                $raw = file_get_contents('php://input') ?: '';
            }
            $raw = is_string($raw) ? trim($raw) : '';
            $decoded = $raw !== '' ? json_decode($raw, true) : null;
            $body = is_array($decoded) ? $decoded : ($request->all() ?: []);
            $action = (string) ($body['action'] ?? $action);
        }
        // Fallback: allow query-string params to fill missing body keys
        // (useful for debugging and for clients that fail to send JSON correctly)
        foreach ($request->query() as $k => $v) {
            if (!array_key_exists($k, $body)) {
                $body[$k] = $v;
            }
        }

        try {
            return match ($action) {
                'init' => $this->init(),

                'getProducts' => response()->json(Product::query()->orderBy('id')->get()),
                'getProduct' => $this->getProduct((int) $request->query('id', 0)),

                'addProduct' => $this->requireKey($request) ?: $this->addProduct($body),
                'updateProduct' => $this->requireKey($request) ?: $this->updateProduct($body),
                'deleteProduct' => $this->requireKey($request) ?: $this->deleteProduct($body),
                'uploadProductImage' => $this->requireKey($request) ?: $this->uploadProductImage($request),

                'getQuotes' => $this->requireKey($request) ?: response()->json(Quote::query()->orderByDesc('id')->get()),
                'addQuote' => $this->addQuote($body),
                'updateQuoteStatus' => $this->requireKey($request) ?: $this->updateQuoteStatus($body),
                'deleteQuote' => $this->requireKey($request) ?: $this->deleteQuote($body),

                'getSitePages' => response()->json(SitePage::query()->orderBy('sort_order')->get()),
                'getSitePage' => $this->getSitePage((string) $request->query('slug', $body['slug'] ?? '')),
                'updateSitePage' => $this->requireKey($request) ?: $this->updateSitePage($body),
                'uploadSitePageImage' => $this->requireKey($request) ?: $this->uploadSitePageImage($request),

                'getHeroSlides' => response()->json(HeroSlide::query()->orderBy('sort_order')->orderBy('id')->get()),
                'addHeroSlide' => $this->requireKey($request) ?: $this->addHeroSlide($body),
                'deleteHeroSlide' => $this->requireKey($request) ?: $this->deleteHeroSlide($body),
                'uploadHeroSlideImage' => $this->requireKey($request) ?: $this->uploadHeroSlideImage($request),

                'getCatalogItems' => response()->json(CatalogItem::query()->where('active', true)->orderBy('sort_order')->orderByDesc('id')->get()),
                'addCatalogItem' => $this->requireKey($request) ?: $this->addCatalogItem($body),
                'deleteCatalogItem' => $this->requireKey($request) ?: $this->deleteCatalogItem($body),
                'uploadCatalogImage' => $this->requireKey($request) ?: $this->uploadCatalogImage($request),

                'getUsers' => $this->requireKey($request) ?: response()->json(
                    User::query()->select(['id', 'full_name', 'email', 'phone', 'role', 'created_at'])->orderByDesc('id')->get()
                ),
                'getUser' => $this->requireKey($request) ?: $this->getUser((int) $request->query('id', 0)),
                'addUser' => $this->requireKey($request) ?: $this->addUser($body),
                'updateUser' => $this->requireKey($request) ?: $this->updateUser($body),
                'deleteUser' => $this->requireKey($request) ?: $this->deleteUser($body),

                'checkAdmin' => $this->checkAdmin($request, $body),
                'checkWebsiteUser' => $this->checkWebsiteUser($body),
                '__debugAdmin' => $this->debugAdmin($body),
                '__debugRequest' => $this->debugRequest($request),

                default => response()->json(['error' => 'Unknown action'], 400),
            };
        } catch (\Throwable $e) {
            $payload = ['error' => 'Server error'];
            if ((bool) config('app.debug')) {
                $payload['details'] = $e->getMessage();
            }
            return response()->json($payload, 500);
        }
    }

    private function allowDevSetup(): bool
    {
        $v = strtolower(trim((string) env('ALLOW_DEV_SETUP', 'false')));
        return in_array($v, ['1', 'true', 'yes', 'y', 'on'], true);
    }

    private function init()
    {
        if (!$this->allowDevSetup()) {
            return response()->json(['error' => 'Not allowed'], 403);
        }

        // Ensure defaults exist (idempotent)
        $this->seedDefaults();
        return response()->json(['ok' => true]);
    }

    private function seedDefaults(): void
    {
        $defaultAdminPassword = (string) (env('DEFAULT_ADMIN_PASSWORD') ?: 'CHANGE_ME');
        $defaultAdminPhone = (string) (env('DEFAULT_ADMIN_PHONE') ?: '01000000000');

        AdminUser::firstOrCreate(
            ['username' => 'admin'],
            ['password' => $defaultAdminPassword]
        );

        User::firstOrCreate(
            ['email' => 'admin'],
            [
                'full_name' => 'مدير النظام',
                'phone' => $defaultAdminPhone,
                'password' => Hash::make($defaultAdminPassword),
                'role' => 'dashboard',
            ]
        );

        $defaults = [
            ['name' => 'أريكة كلاسيك لوكس', 'name_en' => 'Classic Lux Sofa', 'category' => 'living', 'price' => 12500, 'image' => '🛋️', 'description' => 'أريكة فاخرة بتصميم كلاسيكي مع أقمشة مخملية عالية الجودة', 'stock' => 15, 'featured' => true],
            ['name' => 'طاولة طعام رويال', 'name_en' => 'Royal Dining Table', 'category' => 'dining', 'price' => 8900, 'image' => '🪑', 'description' => 'طاولة طعام من خشب الزان الصلب بتشطيب ممتاز', 'stock' => 8, 'featured' => true],
            ['name' => 'سرير كينج مودرن', 'name_en' => 'Modern King Bed', 'category' => 'bedroom', 'price' => 18000, 'image' => '🛏️', 'description' => 'سرير ملكي بتصميم عصري مع لوح رأس مبطن', 'stock' => 6, 'featured' => true],
            ['name' => 'مكتبة بيهايف', 'name_en' => 'Beehive Bookshelf', 'category' => 'office', 'price' => 4500, 'image' => '📚', 'description' => 'مكتبة بتصميم عسلي فريد من خشب البلوط', 'stock' => 20, 'featured' => false],
        ];

        foreach ($defaults as $row) {
            Product::firstOrCreate(['name' => $row['name']], $row);
        }
    }

    private function getProduct(int $id)
    {
        $row = Product::query()->whereKey($id)->first();
        return response()->json($row ?: []);
    }

    private function getUser(int $id)
    {
        $row = User::query()
            ->select(['id', 'full_name', 'email', 'phone', 'role', 'created_at'])
            ->whereKey($id)
            ->first();
        return response()->json($row ?: []);
    }

    private function addProduct(array $body)
    {
        $row = Product::create([
            'name' => (string) ($body['name'] ?? ''),
            'name_en' => (string) ($body['nameEn'] ?? $body['name_en'] ?? ''),
            'category' => (string) ($body['category'] ?? ''),
            'price' => (float) ($body['price'] ?? 0),
            'image' => (string) ($body['image'] ?? ''),
            'description' => (string) ($body['description'] ?? ''),
            'stock' => (int) ($body['stock'] ?? 0),
            'featured' => !empty($body['featured']) ? 1 : 0,
        ]);

        return response()->json($row->fresh() ?? ['id' => $row->id]);
    }

    private function updateProduct(array $body)
    {
        $id = (int) ($body['id'] ?? 0);
        Product::query()->whereKey($id)->update([
            'name' => (string) ($body['name'] ?? ''),
            'name_en' => (string) ($body['nameEn'] ?? $body['name_en'] ?? ''),
            'category' => (string) ($body['category'] ?? ''),
            'price' => (float) ($body['price'] ?? 0),
            'image' => (string) ($body['image'] ?? ''),
            'description' => (string) ($body['description'] ?? ''),
            'stock' => (int) ($body['stock'] ?? 0),
            'featured' => !empty($body['featured']) ? 1 : 0,
        ]);

        return response()->json(['ok' => true]);
    }

    private function deleteProduct(array $body)
    {
        $id = (int) ($body['id'] ?? 0);
        Product::query()->whereKey($id)->delete();
        return response()->json(['ok' => true]);
    }

    private function uploadProductImage(Request $request)
    {
        return $this->uploadPublicImage($request, 'products', 'product');
    }

    private function uploadSitePageImage(Request $request)
    {
        return $this->uploadPublicImage($request, 'pages', 'page');
    }

    private function uploadHeroSlideImage(Request $request)
    {
        return $this->uploadPublicImage($request, 'hero', 'hero');
    }

    private function uploadCatalogImage(Request $request)
    {
        return $this->uploadPublicImage($request, 'catalog', 'catalog');
    }

    private function uploadPublicImage(Request $request, string $folder, string $prefix)
    {
        $file = $request->file('image');
        if (!$file || !$file->isValid()) {
            return response()->json(['error' => 'Image file is required'], 422);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array((string) $file->getMimeType(), $allowedMimes, true)) {
            return response()->json(['error' => 'Only JPG, PNG, WEBP, and GIF images are allowed'], 422);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return response()->json(['error' => 'Image must be 5MB or smaller'], 422);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $extension = match ((string) $file->getMimeType()) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                default => 'jpg',
            };
        }

        $uploadDir = public_path('uploads/' . $folder);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $file->move($uploadDir, $fileName);

        return response()->json([
            'path' => 'uploads/' . $folder . '/' . $fileName,
        ]);
    }

    private function getSitePage(string $slug)
    {
        $page = SitePage::query()->where('slug', $slug)->first();
        return response()->json($page ?: []);
    }

    private function updateSitePage(array $body)
    {
        $slug = (string) ($body['slug'] ?? '');
        $page = SitePage::query()->where('slug', $slug)->first();
        if (!$page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        $page->update([
            'nav_label' => (string) ($body['nav_label'] ?? $body['navLabel'] ?? $page->nav_label),
            'title' => (string) ($body['title'] ?? $page->title),
            'summary' => (string) ($body['summary'] ?? ''),
            'body' => (string) ($body['body'] ?? ''),
            'image' => (string) ($body['image'] ?? ''),
            'links' => $this->normalizeSitePageLinks($body['links'] ?? []),
            'active' => !empty($body['active']) ? 1 : 0,
        ]);

        return response()->json($page->fresh());
    }

    private function normalizeSitePageLinks(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($raw)) {
            return [];
        }

        $links = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            if ($label === '' || $url === '') {
                continue;
            }

            if (!preg_match('/^(https?:\/\/|mailto:|tel:|\/)/i', $url)) {
                $url = 'https://' . $url;
            }

            $links[] = [
                'label' => mb_substr($label, 0, 120),
                'url' => mb_substr($url, 0, 500),
            ];
        }

        return array_slice($links, 0, 20);
    }

    private function addHeroSlide(array $body)
    {
        $image = trim((string) ($body['image'] ?? ''));
        if ($image === '') {
            return response()->json(['error' => 'Image is required'], 422);
        }

        $maxSort = (int) HeroSlide::query()->max('sort_order');
        $row = HeroSlide::create([
            'image' => $image,
            'title' => (string) ($body['title'] ?? ''),
            'sort_order' => $maxSort + 1,
            'active' => true,
        ]);

        return response()->json($row->fresh() ?? ['id' => $row->id]);
    }

    private function deleteHeroSlide(array $body)
    {
        $id = (int) ($body['id'] ?? 0);
        HeroSlide::query()->whereKey($id)->delete();
        return response()->json(['ok' => true]);
    }

    private function addCatalogItem(array $body)
    {
        $name = trim((string) ($body['name'] ?? ''));
        $image = trim((string) ($body['image'] ?? ''));
        if ($name === '' || $image === '') {
            return response()->json(['error' => 'Name and image are required'], 422);
        }

        $maxSort = (int) CatalogItem::query()->max('sort_order');
        $row = CatalogItem::create([
            'name' => mb_substr($name, 0, 255),
            'image' => $image,
            'sort_order' => $maxSort + 1,
            'active' => true,
        ]);

        return response()->json($row->fresh() ?? ['id' => $row->id]);
    }

    private function deleteCatalogItem(array $body)
    {
        $id = (int) ($body['id'] ?? 0);
        CatalogItem::query()->whereKey($id)->delete();
        return response()->json(['ok' => true]);
    }

    private function addQuote(array $body)
    {
        $email = (string) ($body['email'] ?? '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Invalid email'], 422);
        }
        
        $row = Quote::create([
            'name' => (string) ($body['name'] ?? ''),
            'organization' => (string) ($body['organization'] ?? $body['org'] ?? ''),
            'email' => $email,
            'phone' => (string) ($body['phone'] ?? ''),
            'category' => (string) ($body['category'] ?? ''),
            'product' => (string) ($body['product'] ?? ''),
            'quantity' => (int) ($body['quantity'] ?? $body['qty'] ?? 0),
            'budget' => (string) ($body['budget'] ?? ''),
            'specs' => (string) ($body['specs'] ?? ''),
            'notes' => (string) ($body['notes'] ?? ''),
            'file_name' => (string) ($body['fileName'] ?? $body['file_name'] ?? ''),
            'status' => 'جديد',
        ]);

        $mailStatus = $this->sendQuoteEmails($row);

        $payload = $row->fresh()?->toArray() ?? ['id' => $row->id, 'status' => 'جديد'];
        $payload['mail'] = $mailStatus;
        return response()->json($payload);
    }

    private function updateQuoteStatus(array $body)
    {
        $id = (int) ($body['id'] ?? 0);
        $status = (string) ($body['status'] ?? 'جديد');
        Quote::query()->whereKey($id)->update(['status' => $status]);
        return response()->json(['ok' => true]);
    }

    private function deleteQuote(array $body)
    {
        $id = (int) ($body['id'] ?? 0);
        Quote::query()->whereKey($id)->delete();
        return response()->json(['ok' => true]);
    }

    private function addUser(array $body)
    {
        $email = (string) ($body['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Invalid email'], 422);
        }

        $plain = (string) ($body['password'] ?? '');
        if ($plain === '') {
            return response()->json(['error' => 'Password required'], 422);
        }

        try {
            $row = User::create([
                'full_name' => (string) ($body['full_name'] ?? $body['fullName'] ?? ''),
                'email' => $email,
                'phone' => (string) ($body['phone'] ?? ''),
                'password' => Hash::make($plain),
                'role' => (string) ($body['role'] ?? 'website'),
            ]);
        } catch (\Throwable $e) {
            if (str_contains((string) $e->getMessage(), 'Duplicate') || str_contains((string) $e->getMessage(), 'UNIQUE')) {
                return response()->json(['error' => 'اسم الدخول/البريد مستخدم بالفعل'], 409);
            }
            throw $e;
        }

        $safe = User::query()->select(['id', 'full_name', 'email', 'phone', 'role', 'created_at'])->whereKey($row->id)->first();
        return response()->json($safe ?: ['id' => $row->id]);
    }

    private function updateUser(array $body)
    {
        $id = (int) ($body['id'] ?? 0);
        $data = [
            'full_name' => (string) ($body['full_name'] ?? $body['fullName'] ?? ''),
            'email' => (string) ($body['email'] ?? ''),
            'phone' => (string) ($body['phone'] ?? ''),
            'role' => (string) ($body['role'] ?? 'website'),
        ];

        $password = (string) ($body['password'] ?? '');
        if ($password !== '') {
            $data['password'] = Hash::make($password);
        }

        try {
            User::query()->whereKey($id)->update($data);
        } catch (\Throwable $e) {
            if (str_contains((string) $e->getMessage(), 'Duplicate') || str_contains((string) $e->getMessage(), 'UNIQUE')) {
                return response()->json(['error' => 'اسم الدخول/البريد مستخدم بالفعل'], 409);
            }
            throw $e;
        }

        return response()->json(['ok' => true]);
    }

    private function deleteUser(array $body)
    {
        $id = (int) ($body['id'] ?? 0);
        User::query()->whereKey($id)->delete();
        return response()->json(['ok' => true]);
    }

    private function checkAdmin(Request $request, array $body)
    {
        // API-key auth (recommended for production)
        if ($this->dashboardKeyConfigured() && $this->hasValidDashboardKey($request)) {
            return response()->json(['ok' => true]);
        }

        $username = (string) ($body['username'] ?? '');
        $password = (string) ($body['password'] ?? '');

        // dashboard user login (hashed)
        $dashboardUser = User::query()->where('email', $username)->where('role', 'dashboard')->first();
        if ($dashboardUser) {
            $stored = (string) $dashboardUser->password;
            try {
                if (Hash::check($password, $stored)) {
                    return response()->json(['ok' => true]);
                }
            } catch (\Throwable) {
                // Not a valid Laravel hash (legacy plaintext or different algorithm)
            }

            // Legacy support: if old plaintext matches, upgrade to hashed.
            if ($stored !== '' && hash_equals($stored, $password)) {
                $dashboardUser->password = Hash::make($password);
                $dashboardUser->save();
                return response()->json(['ok' => true]);
            }
        }

        // Legacy fallback: if API key is not configured (dev/local), allow admin_users table.
        if (!$this->dashboardKeyConfigured()) {
            $admin = AdminUser::query()->where('username', $username)->first();
            $ok = $admin && is_string($admin->password) && hash_equals($admin->password, $password);
            return response()->json(['ok' => $ok]);
        }

        return response()->json(['ok' => false], 401);
    }

    private function checkWebsiteUser(array $body)
    {
        $username = (string) ($body['username'] ?? '');
        $password = (string) ($body['password'] ?? '');

        $user = User::query()->select(['id', 'full_name', 'email', 'role', 'password'])->where('email', $username)->where('role', 'website')->first();
        if ($user) {
            $stored = (string) $user->password;
            try {
                if (Hash::check($password, $stored)) {
                    return response()->json(['ok' => true, 'user' => $user->only(['id', 'full_name', 'email', 'role'])]);
                }
            } catch (\Throwable) {
                // Not a valid Laravel hash (legacy plaintext or different algorithm)
            }

            // Legacy support: if old plaintext matches, upgrade to hashed.
            if ($stored !== '' && hash_equals($stored, $password)) {
                $user->password = Hash::make($password);
                $user->save();
                return response()->json(['ok' => true, 'user' => $user->only(['id', 'full_name', 'email', 'role'])]);
            }
        }

        return response()->json(['ok' => false], 401);
    }

    private function debugAdmin(array $body)
    {
        if (!(bool) config('app.debug')) {
            return response()->json(['error' => 'Not allowed'], 403);
        }

        $username = (string) ($body['username'] ?? '');
        $password = (string) ($body['password'] ?? '');
        $u = User::query()->where('email', $username)->where('role', 'dashboard')->first();

        if (!$u) {
            return response()->json(['found' => false]);
        }

        $stored = (string) $u->password;
        $hashCheck = null;
        try {
            $hashCheck = Hash::check($password, $stored);
        } catch (\Throwable $e) {
            $hashCheck = 'error: ' . $e->getMessage();
        }
        return response()->json([
            'found' => true,
            'id' => $u->id,
            'email' => $u->email,
            'role' => $u->role,
            'stored_prefix' => substr($stored, 0, 12),
            'stored_len' => strlen($stored),
            'hash_check' => $hashCheck,
            'plaintext_match' => hash_equals($stored, $password),
        ]);
    }

    private function debugRequest(Request $request)
    {
        if (!(bool) config('app.debug')) {
            return response()->json(['error' => 'Not allowed'], 403);
        }

        $raw1 = $request->getContent();
        $raw2 = file_get_contents('php://input') ?: '';
        $raw1 = is_string($raw1) ? $raw1 : '';

        return response()->json([
            'method' => $request->method(),
            'content_type' => (string) $request->header('Content-Type', ''),
            'content_length_header' => (string) $request->header('Content-Length', ''),
            'raw_len_getContent' => strlen($raw1),
            'raw_prefix_getContent' => substr($raw1, 0, 80),
            'raw_len_php_input' => strlen($raw2),
            'raw_prefix_php_input' => substr($raw2, 0, 80),
            'request_all_keys' => array_keys($request->all()),
        ]);
    }

    private function requireKey(Request $request): ?\Illuminate\Http\JsonResponse
    {
        // If not configured, behave like legacy dev mode.
        if (!$this->dashboardKeyConfigured()) {
            return null;
        }
        if (!$this->hasValidDashboardKey($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return null;
    }

    private function dashboardKeyConfigured(): bool
    {
        return trim((string) env('DASHBOARD_API_KEY', '')) !== '';
    }

    private function hasValidDashboardKey(Request $request): bool
    {
        $expected = (string) env('DASHBOARD_API_KEY', '');
        $got = (string) $request->header('X-API-Key', '');

        if ($got === '') {
            $auth = (string) $request->header('Authorization', '');
            if (preg_match('/^\s*Bearer\s+(.+)\s*$/i', $auth, $m)) {
                $got = trim($m[1]);
            }
        }

        return $expected !== '' && hash_equals($expected, $got);
    }

    private function sendQuoteEmails(Quote $quote): array
    {
        $notifyTargets = array_values(array_filter(array_map('trim', explode(',', (string) env('MAIL_NOTIFY', '')))));
        $notifySent = 0;
        $customerSent = false;
        $errors = [];
        $messageIds = [];

        $quoteNo = 'PECU-' . str_pad((string) $quote->id, 4, '0', STR_PAD_LEFT);
        $notifySubject = 'طلب عرض سعر جديد #' . $quoteNo;
        $notifyBody =
            "تم استلام طلب عرض سعر جديد.\n\n" .
            "رقم الطلب: #{$quoteNo}\n" .
            'الاسم: ' . ($quote->name ?? '') . "\n" .
            'الجهة: ' . (($quote->organization ?? '') !== '' ? $quote->organization : '—') . "\n" .
            'البريد: ' . ($quote->email ?? '') . "\n" .
            'الهاتف: ' . ($quote->phone ?? '') . "\n" .
            'الفئة: ' . ($quote->category ?? '') . "\n" .
            'المنتج: ' . (($quote->product ?? '') !== '' ? $quote->product : '—') . "\n" .
            'الكمية: ' . (string) ($quote->quantity ?? 0) . "\n" .
            'الميزانية: ' . (($quote->budget ?? '') !== '' ? $quote->budget : '—') . "\n\n" .
            "المواصفات:\n" . ($quote->specs ?? '') . "\n\n" .
            "ملاحظات:\n" . (($quote->notes ?? '') !== '' ? $quote->notes : 'لا توجد');

        foreach ($notifyTargets as $target) {
            if (!filter_var($target, FILTER_VALIDATE_EMAIL)) {
                $errors[] = ['type' => 'notify', 'to' => $target, 'error' => 'Invalid notify email'];
                continue;
            }
            $result = $this->sendTransactionalEmail($target, $notifySubject, $notifyBody);
            if ($result['sent']) {
                $notifySent++;
                if (!empty($result['message_id'])) {
                    $messageIds[] = ['type' => 'notify', 'to' => $target, 'message_id' => $result['message_id']];
                }
            } else {
                $errors[] = ['type' => 'notify', 'to' => $target, 'provider' => $result['provider'], 'error' => $result['error']];
                Log::warning('Quote notify email failed', ['to' => $target, 'provider' => $result['provider'], 'error' => $result['error']]);
            }
        }

        $customerEmail = (string) ($quote->email ?? '');
        if (filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $customerSubject = 'تم استلام طلب عرض السعر الخاص بك';
            $customerBody =
                "مرحباً " . ($quote->name ?? 'عميلنا الكريم') . "،\n\n" .
                "شكراً لتواصلك مع PECU.\n" .
                "تم استلام طلبك رقم #{$quoteNo} بنجاح، وسيتم التواصل معك خلال 24 ساعة.\n\n" .
                "مع تحيات فريق PECU.";
            $result = $this->sendTransactionalEmail($customerEmail, $customerSubject, $customerBody);
            $customerSent = $result['sent'];
            if ($result['sent'] && !empty($result['message_id'])) {
                $messageIds[] = ['type' => 'customer', 'to' => $customerEmail, 'message_id' => $result['message_id']];
            }
            if (!$result['sent']) {
                $errors[] = ['type' => 'customer', 'to' => $customerEmail, 'provider' => $result['provider'], 'error' => $result['error']];
                Log::warning('Quote customer confirmation email failed', ['to' => $customerEmail, 'provider' => $result['provider'], 'error' => $result['error']]);
            }
        } elseif ($customerEmail !== '') {
            $errors[] = ['type' => 'customer', 'to' => $customerEmail, 'error' => 'Invalid customer email'];
        }

        return [
            'notify_targets' => count($notifyTargets),
            'notify_sent' => $notifySent,
            'customer_sent' => $customerSent,
            'message_ids' => $messageIds,
            'errors' => $errors,
        ];
    }

    private function sendTransactionalEmail(string $to, string $subject, string $body): array
    {
        $brevoKey = trim((string) config('services.brevo.key', ''));
        if ($brevoKey !== '') {
            return $this->sendBrevoEmail($to, $subject, $body, $brevoKey);
        }

        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
                $from = (string) env('MAIL_FROM_ADDRESS', '');
                if ($from !== '') {
                    $message->from($from, (string) env('MAIL_FROM_NAME', 'PECU'));
                    $message->replyTo($from);
                }
            });

            return ['sent' => true, 'provider' => 'smtp', 'message_id' => null, 'error' => null];
        } catch (\Throwable $e) {
            return ['sent' => false, 'provider' => 'smtp', 'message_id' => null, 'error' => $e->getMessage()];
        }
    }

    private function sendBrevoEmail(string $to, string $subject, string $body, string $apiKey): array
    {
        $fromEmail = trim((string) config('services.brevo.from_email', ''));
        $fromName = trim((string) config('services.brevo.from_name', 'PECU')) ?: 'PECU';

        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            return ['sent' => false, 'provider' => 'brevo', 'error' => 'Invalid Brevo sender email'];
        }

        try {
            $response = Http::timeout((int) env('BREVO_TIMEOUT', 10))
                ->acceptJson()
                ->withHeaders(['api-key' => $apiKey])
                ->post('https://api.brevo.com/v3/smtp/email', [
                    'sender' => ['name' => $fromName, 'email' => $fromEmail],
                    'to' => [['email' => $to]],
                    'subject' => $subject,
                    'textContent' => $body,
                ]);

            if ($response->successful()) {
                return [
                    'sent' => true,
                    'provider' => 'brevo',
                    'message_id' => (string) ($response->json('messageId') ?? ''),
                    'error' => null,
                ];
            }

            return [
                'sent' => false,
                'provider' => 'brevo',
                'message_id' => null,
                'error' => 'Brevo API error ' . $response->status() . ': ' . $response->body(),
            ];
        } catch (\Throwable $e) {
            return ['sent' => false, 'provider' => 'brevo', 'message_id' => null, 'error' => $e->getMessage()];
        }
    }
}

