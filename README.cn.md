# AIMonkey

- 全英文
- 前后端分离

## 后端

用php+mysql开发，在根目录index.php中自动加载后端，并在后端`backend/data/`目录中建立`backend/data/reset.php`用以初始化和复位数据库，在后端`backend/data/`目录中建立: struct.sql, config_data.sql(必要的设置数据), basic_data.sql（非必要的内容数据，目前阶段可添加一些测试用的已注册用户信息）来存储数据库结构和基本数据

## 前端
- 用 Vite + pixi.js 开发，
- 使用 @pixi/layout 管理布局
- 使用 @pixi/ui 创建 UI 组件
- 保持架构简洁, 不要在frontend/index.html写入dom, 全部dom树都用js生成，注意代码的复用
- 也在根目录index.php中自动加载后端

# 功能

用户注册：
    - 用户名（必填，同一个邮箱可注册多个分身，账号自动关联，根据后台设置分身数量在登录时提示冻结或激活。）
    - 邮箱（必填）
    - 密码自动分配，并发送至邮箱