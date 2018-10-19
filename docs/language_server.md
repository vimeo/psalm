# Using Psalm's Language Server

Psalm now has built-in Language Server Compatibility support.

It currently only supports diagnostics, with more functionality coming soon.

It works well in a variety of editors:

## emacs

I got it working with [eglot](https://github.com/joaotavora/eglot)

This is the config I used:

```
(require 'eglot)
(add-to-list 'eglot-server-programs '(php-mode . ("php" "vendor/bin/psalm-language-server")))
```

## PhpStorm

Haven't managed to get it working yet in PhpStorm. But soon.

## Sublime Test

I use the excellent Sublime [LSP plugin](https://github.com/tomv564/LSP) with the following config:

```json
        "psalm":
        {
            "command": ["php", "vendor/bin/psalm-language-server"],
            "scopes": ["source.php", "embedding.php"],
            "syntaxes": ["Packages/PHP/PHP.sublime-syntax"],
            "languageId": "php"
        }
```

## vim/neovim

### ALE

[Coming soon](https://github.com/w0rp/ale/pull/2008), but you can use the existing ALE Psalm plugin for now.

### vim-lsp

I got it working with [vim-lsp](https://github.com/prabirshrestha/vim-lsp)

This is the config I used:

```
au User lsp_setup call lsp#register_server({
     \ 'name': 'psalm-language-server',
     \ 'cmd': {server_info->[expand('vendor/bin/psalm-language-server')]},
     \ 'whitelist': ['php'],
     \ })
```

## VS Code

Plugin under development here: https://github.com/psalm/psalm-vscode-plugin
