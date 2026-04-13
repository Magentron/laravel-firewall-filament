<?php

namespace Magentron\LaravelFirewallFilament;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Magentron\LaravelFirewallFilament\Pages\FirewallStatusPage;
use Magentron\LaravelFirewallFilament\Resources\FirewallRuleResource;
use Magentron\LaravelFirewallFilament\Widgets\RecentLogLinesWidget;
use Magentron\LaravelFirewallFilament\Widgets\RuleCountsWidget;

class FirewallFilamentPlugin implements Plugin
{
    protected ?string $navigationGroup = null;

    protected string $slug = 'firewall';

    protected ?Closure $authorizeUsing = null;

    protected bool $enableSettings = false;

    protected bool $enableLogs = false;

    protected bool $enableWidgets = false;

    protected bool $enableRuleCountsWidget = true;

    protected bool $enableRecentLogLinesWidget = true;

    protected bool $allowConfigModeMutations = false;

    public static function make(): static
    {
        return new static();
    }

    public function getId(): string
    {
        return 'magentron-laravel-firewall-filament';
    }

    public function register(Panel $panel): void
    {
        $resources = [
            FirewallRuleResource::class,
        ];
        $pages = [
            FirewallStatusPage::class,
        ];
        $widgets = [];

        if ($this->enableWidgets) {
            if ($this->enableRuleCountsWidget) {
                $widgets[] = RuleCountsWidget::class;
            }
            if ($this->enableRecentLogLinesWidget) {
                $widgets[] = RecentLogLinesWidget::class;
            }
        }

        if ($this->enableSettings) {
            // Settings page will be registered in future stories
        }

        if ($this->enableLogs) {
            // Logs resource will be registered in future stories
        }

        $panel
            ->resources($resources)
            ->pages($pages)
            ->widgets($widgets);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function navigationGroup(?string $group = null): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup;
    }

    public function slug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function authorizeUsing(?Closure $callback): static
    {
        $this->authorizeUsing = $callback;

        return $this;
    }

    public function isAuthorized(): bool
    {
        if ($this->authorizeUsing === null) {
            return false;
        }

        return (bool) ($this->authorizeUsing)();
    }

    public function enableSettings(bool $enable = true): static
    {
        $this->enableSettings = $enable;

        return $this;
    }

    public function hasSettings(): bool
    {
        return $this->enableSettings;
    }

    public function enableLogs(bool $enable = true): static
    {
        $this->enableLogs = $enable;

        return $this;
    }

    public function hasLogs(): bool
    {
        return $this->enableLogs;
    }

    public function enableWidgets(bool $enable = true): static
    {
        $this->enableWidgets = $enable;

        return $this;
    }

    public function hasWidgets(): bool
    {
        return $this->enableWidgets;
    }

    public function enableRuleCountsWidget(bool $enable = true): static
    {
        $this->enableRuleCountsWidget = $enable;

        return $this;
    }

    public function hasRuleCountsWidget(): bool
    {
        return $this->enableRuleCountsWidget;
    }

    public function enableRecentLogLinesWidget(bool $enable = true): static
    {
        $this->enableRecentLogLinesWidget = $enable;

        return $this;
    }

    public function hasRecentLogLinesWidget(): bool
    {
        return $this->enableRecentLogLinesWidget;
    }

    public function allowConfigModeMutations(bool $allow = true): static
    {
        $this->allowConfigModeMutations = $allow;

        return $this;
    }

    public function allowsConfigModeMutations(): bool
    {
        return $this->allowConfigModeMutations;
    }
}
