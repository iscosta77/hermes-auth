# 📷 Exemplo Completo — família iscosta77

Aplicação de demonstração que usa **todas as ferramentas da família** em um único
fluxo real: login → upload múltiplo com drop zone → recorte/WebP/marca d'água →
validação → galeria com grade, carousel e paginação.

## Onde entra cada pacote

| Fluxo | Pacote | Onde no exemplo |
|---|---|---|
| **Banco de dados** | `iscos/voodoo-2026` (Database/Table) | `config.php` (abre o banco), `install.php` (tabelas) |
| **Login/registro/senha** | `iscosta77/auth` | `login.php`, `registro.php`, `logout.php`, rota protegida em `index.php` |
| **Validação de formulário** | `iscosta77/validators` | `registro.php` (regras `required`, `email`, `min`, `same`) |
| **Upload múltiplo (drop zone)** | `iscosta77/upload-multiple` | `index.php` (drop zone JS puro) + `processa_upload.php` (`salvarVarios`) |
| **Upload individual + registro** | `iscosta77/upload` | usado por baixo do upload-multiple (move, valida, grava em `hermes_uploads`) |
| **Recorte/WebP/marca d'água** | `iscosta77/crop-image` | `config.php` (`processamento`) + `processa_upload.php` (thumbs, WebP 80, watermark) |
| **Galeria (grade + carousel)** | `iscosta77/gallery` | `galeria.php` (`Gallery::grade` + `gallery.js` lightbox) |
| **Paginação** | `iscosta77/paginator` | `galeria.php` (`Paginator::paginar` + `links()`) |
| **Image (crop/marca d'água)** | `iscos/voodoo-tools` | alternativa direta ao crop-image (mesmo fluxo) |

## Como rodar

```bash
# 1) instala todas as ferramentas da família
composer install

# 2) cria o banco (sqlite), as tabelas e o usuário admin
php install.php

# 3) sobe o servidor
php -S localhost:8000 -t .

# 4) acesse
#    http://localhost:8000/login.php   → admin@exemplo.com / admin123
```

> Para usar MySQL em produção: troque o DSN em `config.php` para
> `mysql:host=...;dbname=...` — o código é o mesmo.

## O fluxo

1. **Registro/Login** (`iscosta77/auth` + `validators`) — cria conta com validação
   declarativa; sessão segura com `session_regenerate_id`.
2. **Enviar fotos** (`upload-multiple`) — arrasta e solta várias imagens; o lote
   chega em `processa_upload.php`.
3. **Processamento** (`crop-image`) — cada imagem ganha: WebP (qualidade 80),
   thumbs (`400px` e `200x200`) e **marca d'água** com o logo da agência
   (bottom-right, 15% da largura).
4. **Registro no banco** (`upload` + `voodoo-2026`) — grava em `hermes_uploads`
   e `hermes_images`.
5. **Galeria** (`gallery` + `paginator`) — grade responsiva; clique abre o
   **carousel/lightbox** (setas, teclado, ESC); 12 por página com links
   `‹ Anterior 1 2 3 … Próxima ›`.

## Estrutura

```
exemplo/
├── composer.json          ← requer TODOS os pacotes da família
├── config.php             ← banco + pastas + processamento (marca d'água)
├── install.php            ← cria tabelas + admin (voodoo + auth + upload)
├── login.php / registro.php / logout.php
├── index.php              ← drop zone (upload-multiple)
├── processa_upload.php    ← salvarVarios + crop/WebP/watermark
├── galeria.php            ← Gallery + Paginator + lightbox
├── assets/                ← estilo + gallery.css/js
└── public/                ← uploads/, cache/, thumbs/, logo.png (marca d'água)
```

## Licença

MIT — criado e mantido por **Hermes Agent (Nous Research)**, publicado por
**Ildefonso Costa**.
