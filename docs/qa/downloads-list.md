# 资料列表交付说明

## 后台使用

在「网站内容 → 资料下载」（原 `admin.php?page=bl-downloads`）操作：

1. 默认产品画册继承旧 `bl_catalogue_url` / `bl_catalogue_size`；未设置时使用主题自带 PDF。清空默认画册地址可恢复主题文件。
2. 点「添加资料」，选择任意媒体库文件或填写 HTTP/HTTPS 外部地址，再填标题、说明、类型、适用范围。
3. 选择「指定型号」后显示现有风格的型号勾选器。切换范围只隐藏选择器，不丢弃已选型号。
4. 用每行的上下箭头排序，保存资料列表。文件大小由服务端读取，只读；外部文件不发起远程请求，无法确定大小时显示「大小未知」。
5. 默认画册保留稳定标识 `catalogue`，不能删除或改变类型，可排序、更换文件、调整适用范围。其它行可删除。标题、说明留空时默认画册沿用采购与售后原文案；其它资料必须填写标题和地址。
6. 页面下方「历史版本」保留最近 10 次保存前的列表，恢复时也保存当前版本。过期表单拒绝覆盖，输入沿用统一恢复流程保留 24 小时。

## 持久化和兼容

- 保留原后台地址，新增一个 `bl_downloads` option。表单和两个 REST 接口都经过 `bl_content_prepare()` / `bl_content_locked()`；内容读取经过 `bl_content_resolve()`。
- 列表与 `_bl_history` 存在同一个 option 内，一次成功保存只更新这一条业务 option；复用原有的本页保存锁与版本令牌。没有跨记录锁、事务、补偿或产品反向改写。
- 原有页面及历史的 key、slug、URL 不变。没有新建 `bl_content_downloads`、独立历史 option 或接口代理文章。
- 原画册 options 原样保留，只读兼容。去掉旧 URL 更新后连写大小的钩子、`options.php` 表单及直接暴露旧 setting 的旁路；`boblift/v1/catalogue` 仍读写画册，写入统一列表中的默认画册行。
- 旧画册 URL 未变时保留原大小展示；新文件从本地媒体库文件读取字节数。型号名称与历史别名在读取时匹配，不写回产品或资料。
- 默认画册未使用新字段、未添加其它行时保留原模板分支。添加其它资料后，Support 复用下载卡片，产品页按范围筛选并复用下载链接；Support 原有规格表入口与视频卡片保留。感谢页等画册消费者始终只读取默认画册。
- 不修改前台 CSS/JS，不增加依赖。后台复用现有列表、型号选择器和响应式布局类，未新增样式或左侧状态色条。

## REST

两个接口均要求 `edit_pages`，支持 GET / POST / PUT / PATCH：

- `boblift/v1/catalogue`：字段仍为 `bl_catalogue_url` 和只读的 `bl_catalogue_size`；只更新默认画册，保留其它行。外部新链接大小未知时为 0，不继承旧文件大小。
- `boblift/v1/downloads`：返回 `items` 与 `bl_content_revision`。写入 `items` 完整替换列表，数组顺序即排序；可附带读取到的版本令牌防止覆盖他人修改。最多 50 行。
- 每项字段：`id`、`url`、`title`、`description`、`type`、`scope`、`models`。GET 额外返回只读 `size`（字节），写回时必须省略。新行可省略 `id`，服务端生成；原行须保留 ID。
- `type`：`catalogue` / `manual` / `parts` / `specification` / `other`。
- `scope`：`all` / `skid-steer-loaders` / `mini-dumpers` / `models`；`models` 是英文逗号分隔的型号名称，指定型号时必填。

## 验证范围

无数据库 PHP 测试验证旧数据、单 option 写入、顺序、适用范围、别名、大小、非法输入、权限、冲突、失败与历史恢复；模板测试实际渲染 Support、产品和感谢页；Node 测试执行媒体选择及条件显示事件。`tests/fixtures/compare-backend-p4-head.py` 对 HEAD 与工作区进行未经归一化的 39 路由 HTML 字节比较（37 个 sitemap 页面、感谢页、404）。

没有真实 WordPress/数据库实例，本轮不执行 `tests/wp-smoke.php`、真实认证 HTTP 请求或浏览器媒体库操作。布局通过既有类名、响应式规则及 HTML 检查，未进行截图验收。外部地址的可达性不在本轮验证范围。
