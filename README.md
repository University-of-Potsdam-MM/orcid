# ORCID Extension for TYPO3

[![TYPO3](https://img.shields.io/badge/TYPO3-13.x-orange.svg)](https://typo3.org/)
[![PHP](https://img.shields.io/badge/PHP-8.4-blue.svg)](https://www.php.net/)

A TYPO3 extension that integrates researcher profiles and publications from [ORCID (Open Researcher and Contributor ID)](https://orcid.org/) into your TYPO3 website.

## 🌟 Features

- **📚 Display Publications** - Fetch and display scholarly works from ORCID profiles
- **🎨 Multiple Citation Styles** - Support for APA, IEEE, Chicago, Harvard, and more
- **👥 Multiple Researchers** - Support single or multiple ORCID iDs in one content element
- **🔄 Smart Synchronization** - Intelligent incremental updates using timestamps
- **📊 Flexible Grouping** - Group publications by year or type
- **⚡ Performance** - Local database caching for fast page loads
- **🔧 Console Commands** - Easy data management via CLI
- **🌐 Multi-language** - Citation rendering in multiple locales

## 📋 Table of Contents

- [Requirements](#-requirements)
- [Installation](#-installation)
- [Quick Start](#-quick-start)
- [Documentation](#-documentation)
- [Usage Examples](#-usage-examples)
- [Console Commands](#-console-commands)
- [Architecture](#-architecture)
- [Development](#-development)

## 🔧 Requirements

| Requirement | Version    |
|------------|------------|
| **TYPO3** | 13.4.x     |
| **PHP** | 8.4        |
| **PHP Extensions** | curl, json |
| **Database** | MySQL 8.4  |

## 📦 Installation

The extension is distributed via its public GitHub repository (it is not
published on Packagist). Register the repository in your project's
`composer.json`, then require the package — Composer resolves the version from
the repository's git tags.

### 1. Add the repository

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/University-of-Potsdam-MM/orcid.git"
        }
    ]
}
```

### 2. Require the extension

```bash
composer require unipotsdam/orcid:^13.0
```

### Post-Installation

1. **Include Static TypoScript Template**
   - Go to **Web > Template** on your root page
   - Include: **"ORCID Extension (orcid)"**

2. **Configure Extension** (optional)
   - Go to **Admin Tools > Settings > Extension Configuration > orcid**
   - Set API URL (default: `https://pub.orcid.org/v3.0/`)
   - Configure proxy if needed

## 🚀 Quick Start

### 1. Create Content Element

1. Create a new content element on your page
2. Select **"ORCID Work List"** from the plugin section
3. Enter one or more ORCID iDs (format: `0000-0000-0000-0000`)
4. Choose citation style (e.g., APA, IEEE, Harvard)
5. Select grouping option (none, by year, or by type)
6. Save

### 2. Synchronize Data

Run the sync command to fetch publications from ORCID:

```bash
vendor/bin/typo3 orcid:sync
```

### 3. View Frontend

Publications will now display on your page with formatted citations!

## 📖 Documentation

Detailed documentation is available:

- **[Installation Guide](docs/INSTALLATION.md)** - Detailed installation and setup
- **[Configuration](docs/CONFIGURATION.md)** - Extension and content element configuration
- **[User Guide](docs/USAGE.md)** - Editor guide for creating content
- **[Administrator Guide](docs/ADMINISTRATOR.md)** - Admin tasks and maintenance
- **[Developer Guide](docs/DEVELOPMENT.md)** - Extension architecture and customization
- **[API Reference](docs/API.md)** - Class and method documentation
- **[Command Reference](docs/COMMANDS.md)** - Console command details
- **[Changelog](CHANGELOG.md)** - Version history

## 💡 Usage Examples

### Single Researcher Profile

Display publications for one researcher:

```
ORCID iD: 0000-0002-1825-0097
Citation Style: APA
Grouping: By Year
Show Profile Owner: ✓
```

**Result:**
```
Dr. Jane Smith
──────────────

2024
• Smith, J. (2024). Advances in quantum computing...
• Smith, J. & Doe, J. (2024). Machine learning applications...

2023
• Smith, J. (2023). Data science methodologies...
```

### Research Group Publications

Combine publications from multiple researchers:

```
ORCID iDs: 0000-0002-1825-0097, 0000-0003-1234-5678, 0000-0001-9876-5432
Citation Style: IEEE
Grouping: By Type
```

**Result:**
```
Journal Articles
[1] J. Smith, "Article title," Journal Name, vol. 42, no. 3, pp. 123-145, 2024.
[2] J. Doe, "Another article," Different Journal, vol. 15, pp. 78-92, 2024.

Conference Papers
[3] J. Smith and M. Johnson, "Conference paper," in Proc. IEEE Conf., 2024.
```

## 🖥️ Console Commands

### Sync All ORCID Data

Synchronize all ORCID iDs found in content elements:

```bash
vendor/bin/typo3 orcid:sync
```

### Sync Specific ORCID iD

Synchronize a single researcher:

```bash
vendor/bin/typo3 orcid:sync --orcid=0000-0002-1825-0097
```

### Sync Only New Profiles

Sync only ORCID iDs that have never been synced:

```bash
vendor/bin/typo3 orcid:syncNew
```

### Cleanup Unused Data

Delete ORCID data no longer referenced in content:

```bash
vendor/bin/typo3 orcid:cleanup
```

## 🏗️ Architecture

The extension follows **Domain-Driven Design (DDD)** and TYPO3 best practices:

```
Classes/
├── Command/              # Console commands (Symfony Console)
├── Controller/           # Extbase controllers
├── Domain/
│   ├── Mapper/           # Data transformation (API → DTO)
│   └── Repository/       # Data access layer
├── Dto/                  # Data Transfer Objects (readonly)
├── Hooks/                # TYPO3 DataHandler hooks
├── Services/             # Business logic
│   ├── OrcidApiClient.php       # ORCID API integration
│   ├── OrcidSyncService.php     # Sync orchestration
│   └── WorkListRenderer.php     # Citation rendering
└── Value/                # Value Objects (immutable)
    ├── OrcidId.php
    └── OrcidIdCollection.php
```

## 🔨 Development

### Building Frontend Assets

The compiled CSS and JavaScript in `Resources/Public/` are **committed to the
repository** — TYPO3 serves them directly and installations via composer do not
run any build step. When you change a source file under
`Resources/Private/Build/`, rebuild and commit the output.

**Sources → output**

| Source (`Resources/Private/Build/`) | Output (`Resources/Public/`) |
|---|---|
| `StyleSheets/OrcidStyles.scss` | `StyleSheets/OrcidStyles.css` |
| `JavaScript/Vendor/citationjs-entry.js` (bundles `@citation-js/*`) | `JavaScript/Vendor/citation-js.bundle.min.js` |
| `JavaScript/citeproc.js` (vendored citeproc-js, pinned in-tree) | `JavaScript/citeproc.min.js` |
| `JavaScript/orcid-citations.js` | `JavaScript/orcid-citations.min.js` |

**Build**

```bash
npm ci        # install pinned dev dependencies
npm run build # regenerate Resources/Public assets
```

Requires Node `^18`. The build uses `esbuild` (JS) and `sass` (CSS) only — no
gulp. After building, commit the regenerated files in `Resources/Public/`.

> **Note on citeproc-js:** `citeproc.js` is intentionally kept as a vendored
> source file rather than an npm dependency. A newer citeproc release caused
> rendering regressions, so this specific build is pinned in the repository.
> `citation-js` is pinned to exact versions in `package.json`.

### Extending

**Add Custom Citation Style:**

1. Place `.csl` file in `Resources/Public/CitationStyleLanguage/styles/`
2. Add to TCA in `Configuration/TCA/Overrides/tt_content.php`
3. Add translation label

**Override Templates:**

```typoscript
plugin.tx_orcid {
    view {
        templateRootPaths {
            10 = EXT:your_sitepackage/Resources/Private/Extensions/Orcid/Templates/
        }
    }
}
```

**Log to dedicated file:**

Extension logs to normal TYPO3 log. If you want to log to a separate rotating log file:
- Add to `additional.php`
```php
// Route the UniPotsdam\Orcid log channel into its own rotating log file
$GLOBALS['TYPO3_CONF_VARS']['LOG']['UniPotsdam']['Orcid']['writerConfiguration'] = [
    LogLevel::WARNING => [
        RotatingFileWriter::class => [
            'logFileInfix' => 'orcid',
            'interval' => Interval::DAILY,
            'maxFiles' => 7,
        ],
    ],
];
```

## 📊 Database Schema

### Tables

- **`tx_orcid_maindata`** - ORCID profiles and sync status
- **`tx_orcid_workdata`** - Individual scholarly works
- **`tt_content`** - Extended with ORCID fields

### Sync Process

1. **Incremental Updates** - Only fetches changed works
2. **Work Grouping** - ORCID groups the same publication from multiple sources (e.g. Crossref, manual entry) into one logical work. The sync picks the preferred entry per group (highest `display-index`) so duplicates are never stored.
3. **Stale Pruning** - Works no longer present in the API response (or no longer the preferred entry of their group) are automatically deleted during sync.
4. **Rate Limiting** - Respects ORCID API policies (500ms delay)
5. **Status Tracking** - Monitors sync success/failure

## 🛠️ Troubleshooting

### Publications Not Displaying

### API Connection Issues

```bash
# Test ORCID API connectivity
curl -I https://pub.orcid.org/v3.0/

# Check logs
tail -f var/log/orcid_error.log
```

## 🙏 Credits

### Technologies

- **[ORCID Public API v3.0](https://info.orcid.org/documentation/)** - Research profile data
- **[Citation.js](https://citation.js.org/)** - Citation parsing and formatting
- **[citeproc-js](https://citeproc-js.readthedocs.io/)** - CSL processor
- **[Citation Style Language](https://citationstyles.org/)** - Citation formatting standards (CC BY-SA 3.0)

### Development

- **Universität Potsdam** - Initial development and maintenance
- **queonext** - Development support

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/University-of-Potsdam-MM/orcid/issues)
- **ORCID Support**: https://support.orcid.org/

---

**Made with ❤️ for the research community**
