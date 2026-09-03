<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {recipient? : Email address to send test email to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SMTP connection, network reachability, and send a test email with diagnostics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== Checking TA SMTP Diagnostic Test ===');
        $this->newLine();

        $defaultMailer = config('mail.default');
        $smtpConfig = config("mail.mailers.{$defaultMailer}", []);

        $host = $smtpConfig['host'] ?? 'N/A';
        $port = $smtpConfig['port'] ?? 'N/A';
        $scheme = $smtpConfig['scheme'] ?? ($port == 465 ? 'smtps' : 'smtp');
        $username = $smtpConfig['username'] ?? 'N/A';
        $timeout = $smtpConfig['timeout'] ?? 15;
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');

        $this->table(['Setting', 'Value'], [
            ['Default Mailer', $defaultMailer],
            ['SMTP Host', $host],
            ['SMTP Port', $port],
            ['SMTP Scheme', $scheme ?: '(auto)'],
            ['SMTP Username', $username],
            ['SMTP Password', ! empty($smtpConfig['password']) ? '******** (configured)' : '(empty)'],
            ['Socket Timeout', "{$timeout} seconds"],
            ['From Address', "{$fromName} <{$fromAddress}>"],
        ]);

        $recipient = $this->argument('recipient') ?: $fromAddress;
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid recipient email address: '{$recipient}'");

            return Command::FAILURE;
        }

        // 1. Socket Reachability Check
        if ($defaultMailer === 'smtp') {
            $this->newLine();
            $this->line("1. Testing network connection to {$host}:{$port}...");

            $socketTimeout = 5;
            $errno = 0;
            $errstr = '';

            $connectHost = ($port == 465 || $scheme === 'smtps') ? "ssl://{$host}" : $host;
            $fp = @fsockopen($connectHost, $port, $errno, $errstr, $socketTimeout);

            if (! $fp) {
                $this->error("   [FAILED] Could not connect to {$connectHost}:{$port} within {$socketTimeout}s.");
                $this->error("   Error ({$errno}): {$errstr}");
                $this->newLine();
                $this->warn('Possible root causes on shared hosting:');
                $this->line('  a) Outgoing port is blocked by the shared hosting provider firewall (very common for 25, 465, or 587).');
                $this->line('     -> Contact hosting support to unblock outgoing SMTP or use localhost / 127.0.0.1.');
                $this->line('  b) The SMTP hostname is incorrect or unresolvable.');
                $this->line('  c) If using port 465, SSL certificate on host may not match or requires TLS/587 instead.');
                $this->newLine();

                return Command::FAILURE;
            }

            $this->info("   [SUCCESS] Connected to {$connectHost}:{$port} successfully!");
            fclose($fp);
        }

        // 2. Sending Test Email
        $this->newLine();
        $this->line("2. Sending test email via [{$defaultMailer}] to {$recipient}...");

        try {
            Mail::raw('This is a diagnostic test email from Checking TA. Your SMTP configuration is working perfectly!', function ($message) use ($recipient) {
                $message->to($recipient)
                    ->subject('Checking TA - SMTP Test Succeeded');
            });

            $this->info("   [SUCCESS] Test email sent successfully to {$recipient}!");
            $this->line('Please check your inbox (and spam/junk folder).');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("   [FAILED] Failed to send email: {$e->getMessage()}");
            $this->newLine();
            $this->line('Exception Details:');
            $this->line(get_class($e));
            $this->line($e->getFile().':'.$e->getLine());

            Log::error('SMTP Diagnostic Command Failed: '.$e->getMessage(), ['exception' => $e]);

            return Command::FAILURE;
        }
    }
}
