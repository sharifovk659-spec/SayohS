# Чаро «Загрузка» мешавад ва сайт намебарояд?

## Сабаб

Ин лоиҳа **PHP** аст, на HTML.

Агар аз GitHub **Code → Download ZIP** гиред ва файли `index.php`-ро дар браузер кушоед:
- браузер PHP-ро **download** мекунад (загрузка)
- сайт **кор намекунад**

Ҳамин хато дар Vercel ҳам буд: файли PHP ҳамчун матн/download мебаромад, на иҷро.

## Чӣ тавр дуруст кушоед

### Варианти A — локалӣ (Windows)

1. ZIP-ро кушоед (Extract)
2. Ду бор клик: `start-local.bat`
3. Браузер: http://127.0.0.1:8080/

(PHP бояд насб бошад)

### Варианти B — Vercel (Git import)

1. Vercel → Import → репо `SayohS`
2. Deploy (файли `vercel.json` PHP-ро иҷро мекунад)
3. URL: `https://sayoh-s-jekq.vercel.app`

**Муҳим:** MySQL дар Vercel нест. Барои админ/бронь пурра — Hostinger + MySQL.

### Варианти C — Hostinger (тавсия барои прод)

1. Файлҳоро upload кунед
2. `config/database.example.php` → `database.php` (пароли DB)
3. Import SQL: `database/schema.sql` + `seed.sql` (UTF-8)
4. Доменро ба папка пайваст кунед

Муфассал: [INSTALL.md](INSTALL.md) · [DEPLOY_HOSTINGER.md](DEPLOY_HOSTINGER.md)
