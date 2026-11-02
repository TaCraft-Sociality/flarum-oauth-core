# OAuth Core

TCS 系列第三方登录插件的公共依赖插件。

它统一处理所有第三方登录账号共用的部分：

- 账号的邮箱和密码由第三方平台管理，玩家不能在站内自助修改；
- 站内一律不发送邮箱通知，通知只在站内显示；
- 账号使用的虚拟邮箱不会展示给玩家本人（管理员仍可见）；
- 禁止玩家使用保留域名注册或修改邮箱。

单独安装这个插件不会显示任何登录按钮，它需要配合 QQ / GitHub / 微软等登录插件使用。

## 设置

后台「扩展 → OAuth Core」里可以修改**虚拟邮箱后缀**：

- 留空：使用内置的 `fake.invalid`（RFC 2606 保留域，永不解析、无法收信，也不暴露站点域名）；
- 填写：例如 `example.com`，新注册账号会使用这个后缀。必须是带点的合法域名，否则自动回退到 `fake.invalid`。

只影响**新注册**的账号；已有账号的地址不会变。改后缀后，同一个第三方身份在老账号和新账号上算出的地址不同，会被当成两个人。

## 安装

1. 把 OAuth Core 插件目录放到论坛的 `packages`（或你放插件的目录）下。
2. 在 `composer.json` 的 `repositories` 里确认已经配置了该目录的 path 仓库，然后执行：

```bash
composer require tacraft-sociality/flarum-oauth-core:*
```

3. 登录后台，在「扩展」里启用 **OAuth Core**。
4. 执行：

```bash
php flarum cache:clear
```

## 更新

覆盖插件目录后执行：

```bash
composer update tacraft-sociality/flarum-oauth-core
php flarum cache:clear
```

前端有改动时还需要在插件目录的 `js/` 下重新构建：

```bash
npm ci
npm run build
```

## 依赖

- Flarum `^2.0.2`
- PHP `^8.3`

## 许可

MIT
