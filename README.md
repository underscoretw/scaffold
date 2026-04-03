# underscoretw/scaffold

A WP-CLI package that generates starter themes from [underscoretw.com](https://underscoretw.com/).

## Installation

After [installing WP-CLI](https://wp-cli.org/#installing), please run:

```bash
wp package install underscoretw/scaffold
```

## Usage

Run the command without a slug to launch the interactive wizard:

```bash
wp scaffold _tw
```

LLMs or automated processes can generate themes using arguments:

```
wp scaffold _tw [<slug>] [--theme_name=<title>] [--prefix=<prefix>]
    [--theme_uri=<uri>] [--author=<full-name>] [--author_uri=<uri>]
    [--description=<text>] [--activate] [--enable-network] [--force]
```

All of the above arguments also work with the interactive wizard.

`wp scaffold underscoretw` is available as an alias.

### Interactive Wizard

The wizard will prompt for:

1. **Theme name** (required)
2. **Theme slug** (derived from the name by default)
3. **Function prefix** (derived from the slug by default)
4. **Author**, **author URI**, **theme URI**, and **description** (all optional)

After collecting your inputs, the wizard displays a summary and asks for confirmation before generating the theme.

### Options

| Option | Description |
|---|---|
| `<slug>` | The slug for the new theme. If omitted, the interactive wizard is launched. |
| `--theme_name=<title>` | Theme name. Derived from the slug when not set. |
| `--prefix=<prefix>` | Function prefix for the theme. Derived from the slug when not set. |
| `--theme_uri=<uri>` | Theme URI header value. |
| `--author=<full-name>` | Author header value. |
| `--author_uri=<uri>` | Author URI header value. |
| `--description=<text>` | Description header value. |
| `--activate` | Activate the theme after generating it. |
| `--enable-network` | Enable the theme for the entire network after generating it. |
| `--force` | Overwrite the theme directory if it already exists. |

### Examples

Generate a theme using the interactive wizard:

```bash
wp scaffold _tw
```

Generate a theme with default settings:

```bash
wp scaffold _tw my-theme
```

Generate a theme with custom options:

```bash
wp scaffold _tw my-theme --theme_name="My Theme" --author="Jane Doe" --description="A custom theme"
```

Generate and activate:

```bash
wp scaffold _tw my-theme --theme_name="My Theme" --activate
```

Overwrite an existing theme:

```bash
wp scaffold _tw my-theme --force
```

## Credits

This package began as a fork of [`wp-cli/scaffold-command`](https://github.com/wp-cli/scaffold-command). Its wizard was inspired by `npm init`.
