

# SMTP配置
- 注册邮件通过 SMTP 发送，不再使用本机 mail()。
- 配置来源：`app_config`（可供后台管理界面读写）+ 环境变量覆盖（适合生产部署）。

可用环境变量（优先级高于数据库配置）：
- `AIMONKEY_SMTP_ENABLED`：`true` / `false`
- `AIMONKEY_SMTP_HOST`
- `AIMONKEY_SMTP_PORT`（默认 `587`）
- `AIMONKEY_SMTP_SECURE`：`tls` / `ssl` / `none`
- `AIMONKEY_SMTP_USERNAME`
- `AIMONKEY_SMTP_PASSWORD`
- `AIMONKEY_SMTP_FROM_EMAIL`
- `AIMONKEY_SMTP_FROM_NAME`
- `AIMONKEY_SMTP_TIMEOUT`（秒）

后台管理界面可对接接口：
- `GET /api/admin/mail-config`：读取 SMTP 配置（不返回密码明文）
- `POST /api/admin/mail-config`：写入 SMTP 配置
    - 请求体字段：`smtpEnabled`, `host`, `port`, `secure`, `username`, `password`, `fromEmail`, `fromName`, `timeout`
    
可选方案：

免费起步：Resend、Brevo、Mailgun、SendGrid 等免费层
个人邮箱 SMTP：QQ/163/Gmail（通常要开 SMTP 和应用专用密码，不建议高并发生产）
正式上线：建议域名邮箱 + 专业服务，并配置 SPF/DKIM/DMARC