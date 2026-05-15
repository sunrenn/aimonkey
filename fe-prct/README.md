# `create-preact`

<h2 align="center">
  <img height="256" width="256" src="./src/assets/preact.svg">
</h2>

<h3 align="center">Get started using Preact and Vite!</h3>

## Getting Started

-   `npm run dev` - Starts a dev server at http://localhost:5173/

-   `npm run build` - Builds for production, emitting to `dist/`. Prerenders all found routes in app to static HTML

-   `npm run preview` - Starts a server at http://localhost:4173/ to test production build locally


现在登录必须填写email，但是光写email对于多次注册的用户会导致登录错误

所以登录前端页面要把用户名和邮箱合并为一个文本框，根据输入内容是否包含@符号来判断是用户名还是邮箱。

后端相应的修改为：前端提供用户名或者邮箱地址其中任意一个即可。然后去数据库比对密码，只要（用户名+密码）或者（邮箱+密码）的搜索结果存在，并仅存在唯一的结果，即可登录，如果出现了多个结果或者没有，都将提示登录失败