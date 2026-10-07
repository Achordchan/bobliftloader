# P4：历史、新闻正文接管与海报（2026-10-07）

本 PR 删除“改名后关联自动跟随”及配套机制。读取时别名解析留待独立 PR，本次没有实现。未 commit/push。

## 当前范围

- 网站内容页历史保留最近 10 份，记录保存时间、操作人、改动数；恢复前先保存当前内容。保存仍使用 P0 的每页锁、版本校验和冲突输入保留。
- 产品、新闻、案例的 `_bl_record_history` 保留最近 10 份整份记录。每条记录独立加锁，先写历史再写记录；历史失败不写记录，记录失败保留已写历史，无事务或补偿。无变化的普通保存不新增历史，显式恢复即使相同也备份当前值。
- 页面恢复整份快照并执行既有迁移；记录恢复整份 `_bl_record`。型号、属具、相关产品、推荐机型和装柜数据均随快照恢复，没有当前关联保留或装柜合并规则。
- 新闻正文接管继续通过 `post_content_filtered = bl-body-managed` 与正文同一次核心写入，支持明确清空正文；兼容旧记录内和独立 `_bl_body_managed` 的读取。
- 海报后台编辑和 REST 清洗、局部更新继续保留。产品、案例不启用核心 revisions；新闻保留核心 revisions。

## 文件变更

| 文件 | 修改 |
|---|---|
| `inc/wp-product-slug-state.php`、`inc/wp-state-journal.php`、`inc/wp-admin-record-state.php`、`inc/wp-content-relations.php` | 整文件删除：改名同步、歧义提示、图锁、事务、补偿、记录版本表单与 REST 字段。 |
| `inc/wp-record-state.php` | 每条记录独立加锁，先历史后记录；移除跨记录操作和记录版本校验。 |
| `inc/wp-record-history.php` | 整份记录恢复，移除关联保留与版本字段，保留权限、nonce、快照选择、历史表格和独立表单。 |
| `inc/wp-content-state.php` | 直接使用每页锁保存历史和内容；删除同步与属具行标识处理。 |
| `inc/content-state.php` | 删除 tombstone 应用与特殊恢复合并，恢复快照时沿用迁移。 |
| `inc/wp-content-history.php` | 说明改为恢复整份页面。 |
| `inc/wp-admin.php` | 删除记录版本隐藏字段、require 和 `wp-api-fetch` 依赖，基本字段直接调用记录锁。 |
| `inc/wp-product-page.php`、`inc/wp-support.php` | 产品页面字段、视频保存直接调用记录锁。 |
| `inc/wp-rest.php` | 删除记录版本暂存与清理钩子、属具行标识生成；保留页面版本和新闻正文/海报逻辑。 |
| `inc/wp-content-editor.php` | 删除属具行标识隐藏字段及清洗分支；已有 `_bl_id` 作为未知字段忽略。 |
| `assets/admin/post-edit.js` | 删除保存后读取记录版本的订阅及 API 请求。 |
| `tests/test_backend_p4_review.php`、`tests/test_backend_p4_ownership.php`、`tests/test_backend_p4_round5.php` | 删除只覆盖已移除机制的文件。 |
| `tests/test_backend_p4_history.php` | 保留页面/记录历史、新闻正文、海报与 revisions 支持断言。 |
| `tests/test_backend_p4_restore.php` | 改测整份快照恢复、历史上限、失败顺序、独立记录锁、权限、历史表单和旧正文标记。 |
| `tests/test_backend_p0.php` | 更新整体恢复预期，删除记录令牌和废弃参数。 |
| `tests/test_backend_p2.php`、`tests/test_backend_p4.php` | 删除表单中的记录令牌。 |
| `tests/fixtures/wp-memory.php` | 删除事务、补偿、核心修订模拟和已无调用的替身；失败注入仅用于测试历史与记录写入顺序。 |
| `tests/wp-smoke.php` | 删除并发、修订元数据和记录令牌断言，保留历史、空正文、海报和核心 revisions 行为。 |

## 验证结果

- `php tests/*.php` 逐文件运行，排除依赖数据库的 `tests/wp-smoke.php`：18 个脚本通过。
- `node tests/*.cjs` 逐文件运行：2 个脚本通过。
- P4 历史脚本 59 项断言；恢复脚本另有 39 项断言（恢复脚本会先执行历史脚本）。
- 对仍存在的 Git 跟踪 PHP 文件逐个执行 `php -l`：115 个文件通过，含 smoke 脚本，不包含被忽略的 WordPress 核心目录。
- `python3 tests/fixtures/compare-backend-p4-head.py`：39 个默认路由与修改前 HEAD 的 HTML 原始字节一致，无归一化。
- `git diff --check`：通过。检查已删除机制的函数、钩子、require、字段引用，无生产残留。
- 布局影响已静态检查：未修改 CSS 和前台模板；后台移除隐藏字段并缩短说明，保留历史表格、折叠区、空状态和独立表单结构。
- 未新增生产 Mock、调试输出或临时入口；测试替身仅在 tests 目录。

## 未执行与边界

- 未运行依赖数据库的 `tests/wp-smoke.php`、真实 REST HTTP、浏览器、截图或生产验证；本次要求无需数据库验证，布局改动可静态检查。
- 默认路由 HTML 一致不等于验证了所有线上编辑数据。旧 tombstone 不再影响读取；未对已有数据库做清理或数据重写。
- 改名后引用不会自动更新，本次没有别名解析。整份历史恢复会恢复当时的关联值；删除记录版本令牌后不再拒绝过期记录表单。每条记录锁保护保存顺序，不提供跨请求版本冲突检查。
- 记录或页面内容写入失败可能留下额外的历史备份，这是当前顺序写入的预期行为，不进行补偿。
