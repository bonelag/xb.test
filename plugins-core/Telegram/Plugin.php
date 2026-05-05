<?php

namespace Plugin\Telegram;

use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Plugin\AbstractPlugin;
use App\Services\Plugin\HookManager;
use App\Services\TelegramService;
use App\Services\TicketService;
use App\Utils\Helper;
use Illuminate\Support\Facades\Log;

class Plugin extends AbstractPlugin
{
  protected array $commands = [];
  protected TelegramService $telegramService;

  protected array $commandConfigs = [
    '/start' => ['description' => 'Bắt đầu sử dụng', 'handler' => 'handleStartCommand'],
    '/bind' => ['description' => 'Liên kết tài khoản', 'handler' => 'handleBindCommand'],
    '/traffic' => ['description' => 'Xem dung lượng', 'handler' => 'handleTrafficCommand'],
    '/getlatesturl' => ['description' => 'Lấy liên kết đăng ký', 'handler' => 'handleGetLatestUrlCommand'],
    '/unbind' => ['description' => 'Hủy liên kết tài khoản', 'handler' => 'handleUnbindCommand'],
  ];

  public function boot(): void
  {
    $this->telegramService = new TelegramService();
    $this->registerDefaultCommands();

    $this->filter('telegram.message.handle', [$this, 'handleMessage'], 10);
    $this->listen('telegram.message.unhandled', [$this, 'handleUnknownCommand'], 10);
    $this->listen('telegram.message.error', [$this, 'handleError'], 10);
    $this->filter('telegram.bot.commands', [$this, 'addBotCommands'], 10);
    $this->listen('ticket.create.after', [$this, 'sendTicketNotify'], 10);
    $this->listen('ticket.reply.user.after', [$this, 'sendTicketNotify'], 10);
    $this->listen('payment.notify.success', [$this, 'sendPaymentNotify'], 10);
  }

  public function sendPaymentNotify(Order $order): void
  {
    if (!$this->getConfig('enable_payment_notify', true)) {
      return;
    }

    $payment = $order->payment;
    if (!$payment) {
      Log::warning('Thông báo thanh toán thất bại: Phương thức thanh toán liên kết với đơn hàng không tồn tại', ['order_id' => $order->id]);
      return;
    }

    $message = sprintf(
      "💰 Đã nhận thành công %s nhân dân tệ\n" .
      "———————————————\n" .
      "Cổng thanh toán: %s\n" .
      "Kênh thanh toán: %s\n" .
      "Mã đơn hàng trên trang: `%s`",
      $order->total_amount / 100,
      Helper::escapeMarkdown($payment->payment),
      Helper::escapeMarkdown($payment->name),
      $order->trade_no
    );
    $this->telegramService->sendMessageWithAdmin($message, true);
  }

  public function sendTicketNotify(Ticket $ticket): void
  {
    if (!$this->getConfig('enable_ticket_notify', true)) {
      return;
    }

    $message = $ticket->messages()->latest()->first();
    $user = User::find($ticket->user_id);
    if (!$user)
      return;
    $user->load('plan');
    $transfer_enable = $this->transferToGBString($user->transfer_enable);
    $remaining_traffic = $this->transferToGBString($user->transfer_enable - $user->u - $user->d);
    $u = $this->transferToGBString($user->u);
    $d = $this->transferToGBString($user->d);
    $expired_at = $user->expired_at ? date('Y-m-d H:i:s', $user->expired_at) : 'Vô thời hạn';
    $money = $user->balance / 100;
    $affmoney = $user->commission_balance / 100;
    $plan = $user->plan;
    $ip = request()?->ip() ?? '';
    $region = $ip ? (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? (new \Ip2Region())->simple($ip) : 'NULL') : '';
    $TGmessage = "📮 *Nhắc nhở vé hỗ trợ* #{$ticket->id}\n";
    $TGmessage .= "━━━━━━━━━━━━━━━━━━━━\n";
    $TGmessage .= "📧 Email: `{$user->email}`\n";
    $TGmessage .= "📍 Vị trí: `{$region}`\n";

    if ($plan) {
      $TGmessage .= "📦 Gói cước: `" . Helper::escapeMarkdown($plan->name) . "`\n";
      $TGmessage .= "📊 Dung lượng: `{$remaining_traffic}G / {$transfer_enable}G` (Còn lại/Tổng)\n";
      $TGmessage .= "⬆️⬇️ Đã dùng: `{$u}G / {$d}G`\n";
      $TGmessage .= "⏰ Hết hạn: `{$expired_at}`\n";
    } else {
      $TGmessage .= "📦 Gói cước: `Chưa đăng ký gói nào`\n";
    }

    $TGmessage .= "💰 Số dư: `{$money} nhân dân tệ`\n";
    $TGmessage .= "💸 Hoa hồng: `{$affmoney} nhân dân tệ`\n";
    $TGmessage .= "━━━━━━━━━━━━━━━━━━━━\n";
    $TGmessage .= "📝 *Chủ đề*: `" . Helper::escapeMarkdown($ticket->subject) . "`\n";
    $TGmessage .= "💬 *Nội dung*: `" . Helper::escapeMarkdown($message->message) . "`";
    $this->telegramService->sendMessageWithAdmin($TGmessage, true);
  }

  protected function registerDefaultCommands(): void
  {
    foreach ($this->commandConfigs as $command => $config) {
      $this->registerTelegramCommand($command, [$this, $config['handler']]);
    }

    $this->registerReplyHandler('/(📮.*?Nhắc nhở vé hỗ trợ.*?#?|Mã vé: ?)(\\d+)/', [$this, 'handleTicketReply']);
  }

  public function registerTelegramCommand(string $command, callable $handler): void
  {
    $this->commands['commands'][$command] = $handler;
  }

  public function registerReplyHandler(string $regex, callable $handler): void
  {
    $this->commands['replies'][$regex] = $handler;
  }

  /**
   * Gửi tin nhắn cho người dùng
   */
  protected function sendMessage(object $msg, string $message): void
  {
    $this->telegramService->sendMessage($msg->chat_id, $message, 'markdown');
  }

  /**
   * Kiểm tra xem có phải là chat riêng tư không
   */
  protected function checkPrivateChat(object $msg): bool
  {
    if (!$msg->is_private) {
      $this->sendMessage($msg, 'Vui lòng sử dụng lệnh này trong chat riêng tư');
      return false;
    }
    return true;
  }

  /**
   * Lấy người dùng đã liên kết
   */
  protected function getBoundUser(object $msg): ?User
  {
    $user = User::where('telegram_id', $msg->chat_id)->first();
    if (!$user) {
      $this->sendMessage($msg, 'Vui lòng liên kết tài khoản trước');
      return null;
    }
    return $user;
  }

  public function handleStartCommand(object $msg): void
  {
    $welcomeTitle = $this->getConfig('start_welcome_title', '🎉 Chào mừng bạn đến với XBoard Telegram Bot!');
    $botDescription = $this->getConfig('start_bot_description', '🤖 Tôi là trợ lý đắc lực của bạn, có thể giúp bạn:\\n• Liên kết tài khoản XBoard của bạn\\n• Xem tình trạng sử dụng dung lượng\\n• Lấy liên kết đăng ký mới nhất\\n• Quản lý trạng thái liên kết tài khoản');
    $footer = $this->getConfig('start_footer', '💡 Lưu ý: Tất cả các lệnh đều cần được sử dụng trong chat riêng tư');

    $welcomeText = $welcomeTitle . "\n\n" . $botDescription . "\n\n";

    $user = User::where('telegram_id', $msg->chat_id)->first();
    if ($user) {
      $welcomeText .= "✅ Bạn đã liên kết tài khoản: {$user->email}\n\n";
      $welcomeText .= $this->getConfig('start_unbind_guide', '📋 Các lệnh khả dụng:\\n/traffic - Xem tình trạng sử dụng dung lượng\\n/getlatesturl - Lấy liên kết đăng ký\\n/unbind - Hủy liên kết tài khoản');
    } else {
      $welcomeText .= $this->getConfig('start_bind_guide', '🔗 Vui lòng liên kết tài khoản XBoard của bạn trước:\\n1. Đăng nhập vào tài khoản XBoard của bạn\\n2. Sao chép liên kết đăng ký của bạn\\n3. Gửi /bind + liên kết đăng ký') . "\n\n";
      $welcomeText .= $this->getConfig('start_bind_commands', '📋 Các lệnh khả dụng:\\n/bind [liên kết đăng ký] - Liên kết tài khoản');
    }

    $welcomeText .= "\n\n" . $footer;
    $welcomeText = str_replace('\\n', "\n", $welcomeText);

    $this->sendMessage($msg, $welcomeText);
  }

  public function handleMessage(bool $handled, array $data): bool
  {
    list($msg) = $data;
    if ($handled)
      return $handled;

    try {
      return match ($msg->message_type) {
        'message' => $this->handleCommandMessage($msg),
        'reply_message' => $this->handleReplyMessage($msg),
        default => false
      };
    } catch (\Exception $e) {
      Log::error('Lỗi bất ngờ khi xử lý lệnh Telegram', [
        'command' => $msg->command ?? 'unknown',
        'chat_id' => $msg->chat_id ?? 'unknown',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
      ]);

      if (isset($msg->chat_id)) {
        $this->telegramService->sendMessage($msg->chat_id, 'Hệ thống đang bận, vui lòng thử lại sau');
      }

      return true;
    }
  }

  protected function handleCommandMessage(object $msg): bool
  {
    if (!isset($this->commands['commands'][$msg->command])) {
      return false;
    }

    call_user_func($this->commands['commands'][$msg->command], $msg);
    return true;
  }

  protected function handleReplyMessage(object $msg): bool
  {
    if (!isset($this->commands['replies'])) {
      return false;
    }

    foreach ($this->commands['replies'] as $regex => $handler) {
      if (preg_match($regex, $msg->reply_text, $matches)) {
        call_user_func($handler, $msg, $matches);
        return true;
      }
    }

    return false;
  }

  public function handleUnknownCommand(array $data): void
  {
    list($msg) = $data;
    if (!$msg->is_private || $msg->message_type !== 'message')
      return;

    $helpText = $this->getConfig('help_text', 'Lệnh không xác định, vui lòng xem trợ giúp');
    $this->telegramService->sendMessage($msg->chat_id, $helpText);
  }

  public function handleError(array $data): void
  {
    list($msg, $e) = $data;
    Log::error('Lỗi xử lý tin nhắn Telegram', [
      'chat_id' => $msg->chat_id ?? 'unknown',
      'command' => $msg->command ?? 'unknown',
      'message_type' => $msg->message_type ?? 'unknown',
      'error' => $e->getMessage(),
      'file' => $e->getFile(),
      'line' => $e->getLine()
    ]);
  }

  public function handleBindCommand(object $msg): void
  {
    if (!$this->checkPrivateChat($msg)) {
      return;
    }

    $subscribeUrl = $msg->args[0] ?? null;
    if (!$subscribeUrl) {
      $this->sendMessage($msg, 'Tham số không đúng, vui lòng gửi kèm địa chỉ đăng ký');
      return;
    }

    $token = $this->extractTokenFromUrl($subscribeUrl);
    if (!$token) {
      $this->sendMessage($msg, 'Địa chỉ đăng ký không hợp lệ');
      return;
    }

    $user = User::where('token', $token)->first();
    if (!$user) {
      $this->sendMessage($msg, 'Người dùng không tồn tại');
      return;
    }

    if ($user->telegram_id) {
      $this->sendMessage($msg, 'Tài khoản này đã liên kết với tài khoản Telegram');
      return;
    }

    $user->telegram_id = $msg->chat_id;
    if (!$user->save()) {
      $this->sendMessage($msg, 'Thiết lập thất bại');
      return;
    }

    HookManager::call('user.telegram.bind.after', [$user]);
    $this->sendMessage($msg, 'Liên kết thành công');
  }

  protected function extractTokenFromUrl(string $url): ?string
  {
    $parsedUrl = parse_url($url);

    if (isset($parsedUrl['query'])) {
      parse_str($parsedUrl['query'], $query);
      if (isset($query['token'])) {
        return $query['token'];
      }
    }

    if (isset($parsedUrl['path'])) {
      $pathParts = explode('/', trim($parsedUrl['path'], '/'));
      $lastPart = end($pathParts);
      return $lastPart ?: null;
    }

    return null;
  }

  public function handleTrafficCommand(object $msg): void
  {
    if (!$this->checkPrivateChat($msg)) {
      return;
    }

    $user = $this->getBoundUser($msg);
    if (!$user) {
      return;
    }

    $transferUsed = $user->u + $user->d;
    $transferTotal = $user->transfer_enable;
    $transferRemaining = $transferTotal - $transferUsed;
    $usagePercentage = $transferTotal > 0 ? ($transferUsed / $transferTotal) * 100 : 0;

    $text = sprintf(
      "📊 Tình trạng sử dụng dung lượng\n\nĐã dùng: %sG\nTổng dung lượng: %sG\nCòn lại: %sG\nTỷ lệ sử dụng: %.2f%%",
      $this->transferToGBString($transferUsed),
      $this->transferToGBString($transferTotal),
      $this->transferToGBString($transferRemaining),
      $usagePercentage
    );

    $this->sendMessage($msg, $text);
  }

  public function handleGetLatestUrlCommand(object $msg): void
  {
    if (!$this->checkPrivateChat($msg)) {
      return;
    }

    $user = $this->getBoundUser($msg);
    if (!$user) {
      return;
    }

    $subscribeUrl = Helper::getSubscribeUrl($user->token);
    $text = sprintf("🔗 Liên kết đăng ký của bạn:\n\n%s", $subscribeUrl);

    $this->sendMessage($msg, $text);
  }

  public function handleUnbindCommand(object $msg): void
  {
    if (!$this->checkPrivateChat($msg)) {
      return;
    }

    $user = $this->getBoundUser($msg);
    if (!$user) {
      return;
    }

    $user->telegram_id = null;
    if (!$user->save()) {
      $this->sendMessage($msg, 'Hủy liên kết thất bại');
      return;
    }

    $this->sendMessage($msg, 'Hủy liên kết thành công');
  }

  public function handleTicketReply(object $msg, array $matches): void
  {
    $user = $this->getBoundUser($msg);
    if (!$user) {
      return;
    }

    if (!isset($matches[2]) || !is_numeric($matches[2])) {
      Log::warning('Biểu thức chính quy phản hồi vé Telegram không khớp với ID vé', ['matches' => $matches, 'msg' => $msg]);
      $this->sendMessage($msg, 'Không thể nhận diện ID vé, vui lòng trực tiếp phản hồi tin nhắn nhắc nhở vé.');
      return;
    }

    $ticketId = (int) $matches[2];
    $ticket = Ticket::where('id', $ticketId)->first();
    if (!$ticket) {
      $this->sendMessage($msg, 'Vé không tồn tại');
      return;
    }

    $ticketService = new TicketService();
    $ticketService->replyByAdmin(
      $ticketId,
      $msg->text,
      $user->id
    );

    $this->sendMessage($msg, "Phản hồi vé #{$ticketId} thành công");
  }

  /**
   * Thêm các lệnh Bot vào danh sách lệnh
   */
  public function addBotCommands(array $commands): array
  {
    foreach ($this->commandConfigs as $command => $config) {
      $commands[] = [
        'command' => $command,
        'description' => $config['description']
      ];
    }

    return $commands;
  }

  private function transferToGBString(float $transfer_enable, int $decimals = 2): string
  {
    return number_format(Helper::transferToGB($transfer_enable), $decimals, '.', '');
  }

}