# FlavorFlow – Workflow Automation Engine

**Enterprise-grade workflow automation for WordPress.** Define triggers, conditions, and actions to automate any business process — from email notifications to webhook integrations to data updates — all from a modern admin interface.

![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple)
![License](https://img.shields.io/badge/License-GPLv2-green)
![Tests](https://img.shields.io/badge/PHPUnit-56%20tests%20passing-brightgreen)

---

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Directory Structure](#directory-structure)
- [Core Features](#core-features)
- [Installation](#installation)
- [Configuration](#configuration)
- [REST API Reference](#rest-api-reference)
- [Extending FlavorFlow](#extending-flavorflow)
- [Admin UI](#admin-ui)
- [Lite vs Pro](#lite-vs-pro)
- [Testing](#testing)
- [Scaling Considerations](#scaling-considerations)
- [Security](#security)
- [Use Cases](#use-cases)

---

## Overview

FlavorFlow is a modular workflow automation engine built as a WordPress plugin. It follows the **Trigger → Condition → Action** paradigm:

1. **Trigger**: A WordPress event fires (post published, user registered, WooCommerce order, etc.)
2. **Conditions**: The engine evaluates a configurable rule set against the event payload
3. **Actions**: If conditions pass, one or more actions execute in sequence (send email, fire webhook, update data)

Workflows are stored as a custom post type (`ff_workflow`) with structured meta, making them queryable, exportable, and version-controllable.

---

## Architecture

### Design Principles

| Principle | Implementation |
|---|---|
| **PSR-4 Autoloading** | Composer-based, `flavor_flow\` namespace maps to `src/` |
| **SOLID** | Single-responsibility classes, interface-driven extension points, dependency injection |
| **Service Container** | Lightweight DI container with lazy singleton resolution |
| **Service Providers** | Each module registers its services and boots its hooks independently |
| **Registry Pattern** | Triggers, Conditions, and Actions are registered in typed registries |
| **Strategy Pattern** | Each trigger/condition/action implements a shared interface |
| **Immutable DTOs** | `ActionResult` is a value object with named constructors |

### Boot Sequence

```
plugins_loaded
  └─ Plugin::boot()
       ├─ Register all ServiceProviders
       │    ├─ DatabaseServiceProvider    → Migrator
       │    ├─ LogServiceProvider         → Logger
       │    ├─ PostTypeServiceProvider    → WorkflowPostType
       │    ├─ TaxonomyServiceProvider    → WorkflowCategoryTaxonomy
       │    ├─ TriggerServiceProvider     → TriggerRegistry (+ all triggers)
       │    ├─ ConditionServiceProvider   → ConditionEvaluator
       │    ├─ ActionServiceProvider      → ActionRegistry
       │    ├─ QueueServiceProvider       → QueueManager, WorkflowEngine
       │    ├─ ApiServiceProvider         → REST Controllers
       │    ├─ AdminServiceProvider       → AdminPage, SettingsPage
       │    └─ LicenseServiceProvider     → LicenseManager
       ├─ Boot all ServiceProviders (hooks registered)
       └─ do_action('flavor_flow_loaded')
```

### Event Flow

```
WordPress Hook (e.g. transition_post_status)
  └─ Trigger::handle()
       └─ AbstractTrigger::dispatch()
            └─ do_action('flavor_flow_trigger_dispatched', slug, payload)
                 └─ WorkflowEngine::handle_trigger()
                      ├─ Find matching workflows (WP_Query on meta)
                      ├─ Async mode: QueueManager::push() → WP-Cron processes
                      └─ Sync mode: WorkflowEngine::execute_workflow()
                           ├─ ConditionEvaluator::evaluate()
                           └─ ActionInterface::execute() (sequentially)
```

---

## Directory Structure

```
flavor-flow/
├── flavor-flow.php          # Plugin bootstrap (entry point)
├── uninstall.php            # Clean uninstall handler
├── composer.json            # PSR-4 autoloading + dev dependencies
├── phpunit.xml              # PHPUnit configuration
│
├── src/                     # Application code (PSR-4: flavor_flow\)
│   ├── Core/                # Plugin, Container, ServiceProvider, Activator, Deactivator
│   ├── PostType/            # Custom post type registration
│   ├── Taxonomy/            # Custom taxonomy registration
│   ├── Trigger/             # Trigger system (interface, abstract, registry, concrete triggers)
│   │   └── Triggers/        # PostPublished, UserRegistered, UserRoleChanged, CommentPosted, WooCommerce
│   ├── Condition/           # Condition evaluator engine
│   │   └── Conditions/      # StringCondition, NumericCondition
│   ├── Action/              # Action system (interface, registry, result DTO)
│   │   └── Actions/         # SendEmail, SendWebhook, UpdatePostMeta, UpdateUserMeta
│   ├── Queue/               # QueueManager, WorkflowEngine
│   ├── API/                 # REST controllers (Workflow, Log, Webhook Ingress, SystemInfo)
│   ├── Admin/               # Admin pages, settings
│   ├── Logging/             # Logger service
│   ├── License/             # License manager (Lite/Pro)
│   └── Database/            # Migrator (custom tables)
│
├── assets/
│   ├── js/admin/            # React admin SPA (uses @wordpress/element)
│   └── css/admin/           # Admin stylesheet
│
├── templates/
│   ├── admin/               # Admin page templates
│   └── emails/              # Email templates
│
├── tests/
│   ├── Unit/                # PHPUnit unit tests
│   ├── Integration/         # Integration tests (WP test suite)
│   └── bootstrap/           # Test bootstrap with WP function stubs
│
├── config/                  # Container reference documentation
└── languages/               # i18n .pot/.po/.mo files
```

---

## Core Features

### Triggers (Event Sources)

| Trigger | Slug | WordPress Hook |
|---|---|---|
| Post Published | `post_published` | `transition_post_status` |
| User Registered | `user_registered` | `user_register` |
| User Role Changed | `user_role_changed` | `set_user_role` |
| Comment Posted | `comment_posted` | `wp_insert_comment` |
| WooCommerce Order Status | `woo_order_status_changed` | `woocommerce_order_status_changed` |
| Inbound Webhook | `inbound_webhook` | REST endpoint |

### Condition Evaluator

The engine supports nested condition groups with AND/OR logic:

```json
{
  "logic": "AND",
  "rules": [
    { "field": "post_type", "type": "string", "operator": "equals", "value": "post" },
    {
      "logic": "OR",
      "rules": [
        { "field": "author_id", "type": "numeric", "operator": "equals", "value": "1" },
        { "field": "author_id", "type": "numeric", "operator": "equals", "value": "2" }
      ]
    }
  ]
}
```

**String operators:** equals, not_equals, contains, not_contains, starts_with, ends_with, is_empty, is_not_empty

**Numeric operators:** equals, not_equals, greater_than, less_than, greater_than_equal, less_than_equal

Supports dot-notation field access (`user.role`, `order.billing_email`).

### Actions

| Action | Slug | Description |
|---|---|---|
| Send Email | `send_email` | Sends HTML email with `{{placeholder}}` interpolation |
| Send Webhook | `send_webhook` | HTTP request to external URL with JSON payload |
| Update Post Meta | `update_post_meta` | Sets a post meta value |
| Update User Meta | `update_user_meta` | Sets a user meta value |

### Queue System

- Custom database table (`wp_flavor_flow_queue`)
- WP-Cron processing every minute
- Configurable retry logic (max attempts, exponential backoff possible)
- Atomic job claiming (prevents duplicate execution)
- Failed job tracking
- Automatic purge of old completed/failed jobs

### Logging

- Custom database table (`wp_flavor_flow_logs`)
- Levels: debug, info, warning, error
- Configurable retention period
- REST API for log querying and purging
- Automatic daily cleanup via WP-Cron

---

## Installation

### Requirements

- WordPress 6.0+
- PHP 8.0+
- Composer (for development)

### Setup

```bash
# Clone the repository
git clone https://github.com/flavancio/enterprise-grade-wp-plugin.git

# Install dependencies
cd enterprise-grade-wp-plugin
composer install

# Copy to your WordPress plugins directory
# Activate via wp-admin or WP-CLI:
wp plugin activate flavor-flow
```

---

## Configuration

### Settings

Navigate to **FlavorFlow → Settings** in wp-admin:

| Setting | Default | Description |
|---|---|---|
| Execution Mode | `async` | `async` (queue-based) or `sync` (immediate) |
| Max Retries | 3 | Maximum retry attempts for failed queue jobs |
| Webhook Timeout | 15s | HTTP timeout for outgoing webhooks |
| Enable Logging | On | Toggle structured logging |
| Log Retention | 30 days | Auto-purge logs older than this |

### Creating a Workflow

1. Go to **FlavorFlow → Workflows**
2. Click **+ New Workflow**
3. Name the workflow
4. Select a **Trigger** (e.g., "Post Published")
5. Add **Conditions** (e.g., `post_type equals post AND author_id greater_than 0`)
6. Add **Actions** (e.g., Send Email with `{{post_title}}` in the subject)
7. Toggle **Enabled** and save

---

## REST API Reference

All endpoints are under `/wp-json/flavor-flow/v1/`.

### Workflows

| Method | Endpoint | Description |
|---|---|---|
| GET | `/workflows` | List all workflows |
| POST | `/workflows` | Create a workflow |
| GET | `/workflows/{id}` | Get a single workflow |
| PUT | `/workflows/{id}` | Update a workflow |
| DELETE | `/workflows/{id}` | Delete a workflow |
| POST | `/workflows/{id}/duplicate` | Duplicate a workflow |
| PUT | `/workflows/{id}/toggle` | Toggle enabled/disabled |

### Logs

| Method | Endpoint | Description |
|---|---|---|
| GET | `/logs` | Query execution logs |
| DELETE | `/logs/purge` | Purge all logs |

### Settings

| Method | Endpoint | Description |
|---|---|---|
| GET | `/settings` | Get current settings |
| PUT | `/settings` | Update settings |

### System

| Method | Endpoint | Description |
|---|---|---|
| GET | `/system` | List available triggers, conditions, and actions |

### License

| Method | Endpoint | Description |
|---|---|---|
| POST | `/license/activate` | Activate a license key |
| POST | `/license/deactivate` | Deactivate the license |
| GET | `/license/status` | Check license status |

### Webhook Ingress

| Method | Endpoint | Description |
|---|---|---|
| POST | `/webhook/{token}` | Receive external webhook (token-based auth) |

All authenticated endpoints require `manage_options` or `edit_ff_workflows` capability. The REST API uses WordPress nonce-based authentication via `wp-api-fetch`.

---

## Extending FlavorFlow

### Adding a Custom Trigger

```php
use flavor_flow\Trigger\AbstractTrigger;

class MyCustomTrigger extends AbstractTrigger {
    public function get_slug(): string { return 'my_custom_event'; }
    public function get_label(): string { return 'My Custom Event'; }
    public function get_group(): string { return 'Custom'; }

    public function listen(): void {
        add_action( 'my_plugin_event', [ $this, 'handle' ] );
    }

    public function handle( $data ): void {
        $this->dispatch( [ 'key' => $data ] );
    }

    public function get_payload_schema(): array {
        return [
            'type' => 'object',
            'properties' => [ 'key' => [ 'type' => 'string' ] ],
        ];
    }
}

// Register it:
add_filter( 'flavor_flow_triggers', function( array $triggers ): array {
    $triggers[] = new MyCustomTrigger();
    return $triggers;
} );
```

### Adding a Custom Action

```php
use flavor_flow\Action\ActionInterface;
use flavor_flow\Action\ActionResult;

class SlackNotifyAction implements ActionInterface {
    public function get_slug(): string { return 'slack_notify'; }
    public function get_label(): string { return 'Send Slack Message'; }
    public function get_group(): string { return 'Communication'; }

    public function execute( array $config, array $payload ): ActionResult {
        // Your Slack API call here...
        return ActionResult::success( 'Slack message sent.' );
    }

    public function get_config_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'webhook_url' => [ 'type' => 'string', 'format' => 'uri' ],
                'message' => [ 'type' => 'string' ],
            ],
        ];
    }
}

add_filter( 'flavor_flow_actions', function( array $actions ): array {
    $actions[] = new SlackNotifyAction();
    return $actions;
} );
```

### Adding a Custom Condition Type

```php
use flavor_flow\Condition\ConditionInterface;

class DateCondition implements ConditionInterface {
    public function get_slug(): string { return 'date'; }
    public function get_label(): string { return 'Date'; }

    public function evaluate( mixed $actual, string $operator, mixed $expected ): bool {
        // Date comparison logic...
    }

    public function get_operators(): array {
        return [
            'before' => 'Before',
            'after' => 'After',
            'same_day' => 'Same Day',
        ];
    }
}

add_filter( 'flavor_flow_condition_types', function( array $types ): array {
    $types[] = new DateCondition();
    return $types;
} );
```

### Available Filter Hooks

| Hook | Description |
|---|---|
| `flavor_flow_service_providers` | Add/remove service providers |
| `flavor_flow_triggers` | Register custom triggers |
| `flavor_flow_actions` | Register custom actions |
| `flavor_flow_condition_types` | Register custom condition types |

### Available Action Hooks

| Hook | Description |
|---|---|
| `flavor_flow_loaded` | Fires after plugin is fully booted |
| `flavor_flow_trigger_dispatched` | Fires when any trigger dispatches |

---

## Admin UI

The admin interface is a React single-page application built on `@wordpress/element` and `@wordpress/components`.

### Pages

- **Workflows** — CRUD interface with list view and visual editor
- **Logs** — Paginated execution log viewer with status badges
- **Settings** — General, logging, and execution configuration
- **License** — Pro license activation/deactivation

### Workflow Editor Features

- Trigger selector with grouped options
- Conditional logic builder with AND/OR grouping
- Dynamic payload field suggestions based on selected trigger
- Ordered action chain with per-action configuration
- Enable/disable toggle
- Duplicate and delete operations

### Screenshots

**Workflow List View:**
The workflows page displays all workflows in a table with name, trigger, status badges (Active/Inactive), and action buttons (Enable/Disable, Duplicate, Delete).

**Workflow Editor:**
The editor features cards for Workflow Name, Trigger Selection, Conditions (with logic toggle and rule rows), and Actions (stackable action cards with dynamic configuration fields).

**Settings Page:**
Settings are organized into General Settings (execution mode, retries, webhook timeout) and Logging (toggle, retention period) cards.

**Logs Viewer:**
Paginated table showing log ID, workflow ID, trigger name, status badge (info/error/warning), message, and timestamp.

---

## Lite vs Pro

FlavorFlow ships with a **Lite/Pro architecture** ready for monetization:

| Feature | Lite | Pro |
|---|---|---|
| Workflows | 5 max | Unlimited |
| Core triggers (Post, User, Comment) | Yes | Yes |
| WooCommerce triggers | No | Yes |
| Basic conditions | Yes | Yes |
| Nested condition groups | No | Yes |
| Email & post meta actions | Yes | Yes |
| Webhook actions | No | Yes |
| Webhook ingress | No | Yes |
| Queue-based execution | Yes | Yes |
| Logging | Yes | Yes |
| Priority support | No | Yes |
| Scheduled triggers | No | Yes |

### License System

- Remote API validation at `https://api.flavancio.io/license/`
- Activation, deactivation, and periodic re-validation
- Weekly cron-based license check
- License key masking in REST responses
- Ready for EDD Software Licensing, WooCommerce SL, or custom server

---

## Testing

```bash
# Install dependencies
composer install

# Run all tests
vendor/bin/phpunit

# Run with test names
vendor/bin/phpunit --testdox

# Run specific suite
vendor/bin/phpunit --testsuite Unit
```

### Test Coverage

- **Container** — Singleton behavior, factory injection, error handling
- **ConditionEvaluator** — AND/OR logic, nested groups, dot-notation, all operators
- **StringCondition** — All 8 operators with data providers
- **NumericCondition** — All 6 operators with data providers
- **ActionResult** — Success/failure factories, serialization
- **TriggerRegistry** — Registration, lookup, grouping
- **ActionRegistry** — Registration, lookup, grouping

**56 tests, 79 assertions — all passing.**

---

## Scaling Considerations

### Database

- Custom tables use indexed columns (`workflow_id`, `status`, `created_at`, `scheduled_at`)
- Queue table uses composite index on `(status, scheduled_at)` for efficient job claiming
- Automatic cleanup of old logs and completed queue jobs
- Consider partitioning the logs table for high-volume installations

### Queue Processing

- Default: WP-Cron every 60 seconds
- For high-throughput: Replace WP-Cron with a real cron job (`wp cron event run flavor_flow_process_queue`)
- Claims jobs in batches of 10 (configurable)
- Atomic claim with `UPDATE … WHERE status = 'pending'` prevents double execution
- **Action Scheduler alternative**: The architecture is designed to swap `QueueManager` for WooCommerce's Action Scheduler with minimal changes

### Horizontal Scaling

- Stateless execution — no in-memory state between requests
- Queue is database-backed — works across multiple app servers
- Atomic job claiming prevents race conditions
- Consider external queue (Redis, RabbitMQ) for extreme scale

### Caching

- Workflow queries are standard `WP_Query` — benefits from object cache (Redis/Memcached)
- Settings use `get_option()` — cached by WordPress core
- Consider transient caching for system info endpoint

---

## Security

### Input/Output

- All user input sanitized with `sanitize_text_field()`, `sanitize_key()`, `absint()`, `esc_url_raw()`
- Output escaped with `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- REST request data validated via `sanitize_callback` on every argument
- Recursive sanitization for nested structures (`sanitize_deep`)

### Authentication & Authorization

- All admin REST endpoints require `manage_options` or `edit_ff_workflows` capability
- Custom capability types for the workflow post type (`edit_ff_workflow`, `delete_ff_workflow`, etc.)
- Webhook ingress uses token-based authentication (32-64 char tokens)
- WP nonce verification for admin AJAX via `wp_create_nonce('wp_rest')`

### Data Protection

- License keys masked in REST responses
- Direct file access prevented (`if ( ! defined( 'ABSPATH' ) ) exit;`)
- Prepared statements for all direct database queries
- No `eval()`, no file includes from user input
- SSL verification enabled on outbound HTTP requests

### WordPress Standards

- Follows WordPress Coding Standards (WPCS)
- Uses `wp_remote_*` functions (not cURL directly)
- Uses `$wpdb->prepare()` for all custom SQL
- Properly hooked into activation/deactivation/uninstall lifecycle

---

## Use Cases

### Content Pipeline Automation
> When a post is published, check if it's in the "News" category, then send a Slack notification and update a custom field.

### User Onboarding
> When a new user registers, send a welcome email and set up their profile meta.

### E-Commerce Workflows
> When a WooCommerce order status changes to "completed", fire a webhook to your fulfillment API and send a custom confirmation email.

### Compliance & Audit
> When a user role changes, log the event and notify the admin team via email.

### External System Integration
> Receive incoming webhooks from Stripe, Zapier, or any external system and trigger internal WordPress actions.

---

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

Please ensure all tests pass and follow WordPress Coding Standards.

---

## License

Licensed under the GNU General Public License v2.0 or later.
See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) for details.

---

Built by **Flavancio Engineering** — enterprise WordPress solutions.
