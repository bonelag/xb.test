<?php

namespace App\Http\Controllers\V2\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Services\Plugin\PluginManager;
use App\Services\Plugin\PluginConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PluginController extends Controller
{
    protected PluginManager $pluginManager;
    protected PluginConfigService $configService;

    public function __construct(
        PluginManager $pluginManager,
        PluginConfigService $configService
    ) {
        $this->pluginManager = $pluginManager;
        $this->configService = $configService;
    }

    /**
     * 获取所有插件类型
     */
    public function types()
    {
        return response()->json([
            'data' => [
                [
                    'value' => Plugin::TYPE_FEATURE,
                    'label' => 'Tính năng',
                    'description' => 'Plugin mở rộng tính năng, ví dụ: đăng nhập Telegram, thông báo email...',
                    'icon' => '🔧'
                ],
                [
                    'value' => Plugin::TYPE_PAYMENT,
                    'label' => 'Thanh toán',
                    'description' => 'Plugin cổng thanh toán, ví dụ: Alipay, WeChat Pay...',
                    'icon' => '💳'
                ]
            ]
        ]);
    }

    /**
     * 获取插件列表
     */
    public function index(Request $request)
    {
        $type = $request->query('type');

        $installedPlugins = Plugin::when($type, function ($query) use ($type) {
            return $query->byType($type);
        })
            ->get()
            ->keyBy('code')
            ->toArray();

        $plugins = [];
        $seenCodes = [];

        foreach ($this->pluginManager->getPluginPaths() as $pluginPath) {
            if (!File::exists($pluginPath)) {
                continue;
            }
            $directories = File::directories($pluginPath);
            foreach ($directories as $directory) {
                $configFile = $directory . '/config.json';
                if (!File::exists($configFile)) {
                    continue;
                }
                $config = json_decode(File::get($configFile), true);
                if (!$config || !isset($config['code'])) {
                    continue;
                }
                $code = $config['code'];

                if (isset($seenCodes[$code])) {
                    continue;
                }
                $seenCodes[$code] = true;

                $pluginType = $config['type'] ?? Plugin::TYPE_FEATURE;
                if ($type && $pluginType !== $type) {
                    continue;
                }

                $installed = isset($installedPlugins[$code]);
                $pluginConfig = $installed ? $this->configService->getConfig($code) : ($config['config'] ?? []);
                $readmeFile = collect(['README.md', 'readme.md'])
                    ->map(fn($f) => $directory . '/' . $f)
                    ->first(fn($path) => File::exists($path));
                $readmeContent = $readmeFile ? File::get($readmeFile) : '';
                $needUpgrade = false;
                if ($installed) {
                    $installedVersion = $installedPlugins[$code]['version'] ?? null;
                    $localVersion = $config['version'] ?? null;
                    if ($installedVersion && $localVersion && version_compare($localVersion, $installedVersion, '>')) {
                        $needUpgrade = true;
                    }
                }
                $isCore = $this->pluginManager->isCorePlugin($code);
                $plugins[] = [
                    'code' => $config['code'],
                    'name' => $config['name'],
                    'version' => $config['version'],
                    'description' => $config['description'],
                    'author' => $config['author'],
                    'type' => $pluginType,
                    'is_installed' => $installed,
                    'is_enabled' => $installed ? $installedPlugins[$code]['is_enabled'] : false,
                    'is_protected' => $isCore,
                    'can_be_deleted' => !$isCore,
                    'config' => $pluginConfig,
                    'readme' => $readmeContent,
                    'need_upgrade' => $needUpgrade,
                    'admin_menus' => $config['admin_menus'] ?? null,
                    'admin_crud' => $config['admin_crud'] ?? null,
                ];
            }
        }

        return response()->json([
            'data' => $plugins
        ]);
    }

    /**
     * 安装插件
     */
    public function install(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        try {
            $this->pluginManager->install($request->input('code'));
            return response()->json([
                'message' => 'Cài đặt plugin thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Cài đặt plugin thất bại: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 卸载插件
     */
    public function uninstall(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $code = $request->input('code');
        $plugin = Plugin::where('code', $code)->first();
        if ($plugin && $plugin->is_enabled) {
            return response()->json([
                'message' => 'Vui lòng tắt plugin trước khi gỡ cài đặt'
            ], 400);
        }

        try {
            $this->pluginManager->uninstall($code);
            return response()->json([
                'message' => 'Gỡ cài đặt plugin thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gỡ cài đặt plugin thất bại: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 升级插件
     */
    public function upgrade(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);
        try {
            $this->pluginManager->update($request->input('code'));
            return response()->json([
                'message' => 'Nâng cấp plugin thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Nâng cấp plugin thất bại: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 启用插件
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        try {
            $this->pluginManager->enable($request->input('code'));
            return response()->json([
                'message' => 'Bật plugin thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Bật plugin thất bại: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 禁用插件
     */
    public function disable(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $this->pluginManager->disable($request->input('code'));
        return response()->json([
            'message' => 'Tắt plugin thành công'
        ]);

    }

    /**
     * 获取插件配置
     */
    public function getConfig(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        try {
            $config = $this->configService->getConfig($request->input('code'));
            return response()->json([
                'data' => $config
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lấy cấu hình thất bại: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 更新插件配置
     */
    public function updateConfig(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'config' => 'required|array'
        ]);

        try {
            $this->configService->updateConfig(
                $request->input('code'),
                $request->input('config')
            );

            return response()->json([
                'message' => 'Cập nhật cấu hình thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Cập nhật cấu hình thất bại: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 上传插件
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:zip',
                'max:10240', // 最大10MB
            ]
        ], [
            'file.required' => 'Vui lòng chọn file plugin',
            'file.file' => 'Loại file không hợp lệ',
            'file.mimes' => 'Plugin phải có định dạng zip',
            'file.max' => 'Kích thước plugin không được vượt quá 10MB'
        ]);

        try {
            $this->pluginManager->upload($request->file('file'));
            return response()->json([
                'message' => 'Tải lên plugin thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Tải lên plugin thất bại: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 删除插件
     */
    public function delete(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $code = $request->input('code');

        // 检查是否为核心插件
        if ($this->pluginManager->isCorePlugin($code)) {
            return response()->json([
                'message' => 'Plugin này là plugin cốt lõi của hệ thống, không được phép xóa'
            ], 403);
        }

        try {
            $this->pluginManager->delete($code);
            return response()->json([
                'message' => 'Xóa plugin thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Xóa plugin thất bại: ' . $e->getMessage()
            ], 400);
        }
    }
}