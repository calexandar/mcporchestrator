<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mcp\Auth\McpScope;
use App\Mcp\Support\McpTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\text;

class CreateMcpTokenCommand extends Command
{
    protected $signature = 'mcp:token
        {--name= : Token name}
        {--scopes= : Comma separated scopes, e.g. project:read,task:read}
        {--days= : Expiration in days (0 or empty for no expiration)}';

    protected $description = 'Create an MCP bearer token for the MCP endpoint';

    public function __construct(private readonly McpTokenService $tokens)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->option('name');
        $scopes = $this->option('scopes');
        $days = $this->option('days');

        if ($this->input->isInteractive()) {
            $name = $name !== null && $name !== '' ? $name : text(
                label: 'Token name',
                placeholder: 'OpenCode Local',
                required: true,
            );

            if ($scopes === null || $scopes === '') {
                $selected = multiselect(
                    label: 'Scopes',
                    options: McpScope::values(),
                    default: McpScope::values(),
                    required: true,
                );

                $scopes = implode(',', $selected);
            }

            if ($days === null) {
                $days = text(
                    label: 'Expiration (days, empty for none)',
                    default: '30',
                    validate: fn (string $value): ?string => $value === '' || ctype_digit($value)
                        ? null
                        : 'Expiration must be a positive number of days.',

                );
            }
        }

        if ($name === null || $name === '') {
            $this->error('A token name is required. Use php artisan mcp:token --name="OpenCode Local"');

            return self::FAILURE;
        }

        $scopeValues = array_values(array_filter(array_map('trim', explode(',', (string) ($scopes ?? '')))));
        $expiresInDays = $days !== null && $days !== '' && (int) $days > 0 ? (int) $days : null;

        try {
            $result = $this->tokens->generate(
                name: $name,
                scopes: $scopeValues === [] ? McpScope::values() : $scopeValues,
                expiresInDays: $expiresInDays,
                createdBy: Auth::id() !== null ? (int) Auth::id() : null,
            );
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('MCP token created.');

        $this->warn('IMPORTANT: This token will only be displayed once.');
        $this->line('');

        $this->line('<options=bold>'.$result['raw'].'</>');

        $this->newLine();
        $this->info('Add it to your environment without committing it:');
        $this->line('LARAVEL_MCP_TOKEN='.$result['raw']);

        return self::SUCCESS;
    }
}
