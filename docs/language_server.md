# Using Psalm's Language Server

Psalm now has built-in Language Server Compatibility support so you can run it in your favourite IDE.

It currently only supports diagnostics (i.e. finding errors and warnings), but more functionality is coming soon.

It works well in a variety of editors (listed alphabetically):

## emacs

I got it working with [eglot](https://github.com/joaotavora/eglot)

This is the config I used:

```
(when (file-exists-p "vendor/bin/psalm-language-server")
  (progn
    (require 'php-mode)
    (require 'eglot)
    (add-to-list 'eglot-server-programs '(php-mode . ("php" "vendor/bin/psalm-language-server")))
    (add-hook 'php-mode-hook 'eglot-ensure)
    (advice-add 'eglot-eldoc-function :around
                (lambda (oldfun)
                  (let ((help (help-at-pt-kbd-string)))
                    (if help (message "%s" help) (funcall oldfun)))))
    )
  )
```

## PhpStorm

I've got it working with this plugin: https://plugins.jetbrains.com/plugin/10209-lsp-support.

Setup is done via a GUI.

When you install the plugin you should see a "Language Server Protocol" section under the "Languages & Frameworks" tab.

In the "Server definitions" tab you should add a definition for Psalm:
 - Extension: `php`
 - Path: `<path-to-php-binary>` e.g. `/usr/local/bin/php`
    - this should be an absolute path, not just `php`
 - Args: `vendor/bin/psalm-language-server`

In the "Timeouts" tab you can adjust the initialization timeout. This is important if you have a large project. You should set the "Init" value to the number of milliseconds you allow Psalm to scan your entire project and your project's dependencies.

## Sublime Text

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

[Coming soon](https://github.com/w0rp/ale/pull/2013), but you can use the existing ALE Psalm plugin for now.

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

Get the Psalm plugin here (Requires VS Code 1.26+): https://marketplace.visualstudio.com/items?itemName=getpsalm.psalm-vscode-plugin
