<?php
/** Defaults retain the original frontend strings; sections feed the shared content editor. */
return [
    'defaults' => [
        'body' => [
            'intro' => '<h2>Who this website represents</h2><p>{company.domain} is the loader equipment business website of {company.name} Contact the company at <a href="mailto:{company.email}">{company.email}</a>.</p>',
            'submitted' => '<h2>How the enquiry form works</h2><p>The form sends your enquiry to the BOB-LIFT team by email. Choose your equipment, add your contact details and a short description of your project, then press “Send enquiry”.</p>
<h2>Your information</h2><p>Your enquiry contains what you enter in the form: your equipment choice, name, business email, company and project details. It is emailed to the company and a copy is kept in this website’s administration area so the team can follow it up. The email also notes the page you sent it from, your IP address and the country that address belongs to (looked up with ipinfo.io). The company uses it to respond to your enquiry. If a spam-protection service such as Cloudflare Turnstile is switched on, it checks your browser when you send the form.</p>
',
            'draft' => '<h2>How the enquiry form works</h2><p>The form prepares an email draft on your device. You can review your product choice and contact details before opening your email application. Nothing is sent by pressing “Prepare enquiry”; you choose whether to send the email from your email application.</p><h2>Your information</h2><p>Form entries are used to prepare the draft and are not stored in a website database by this form. When you send an email, the company receives the information you include and can use it to respond to your enquiry. Your email provider’s own privacy terms also apply.</p>',
            'ending' => '<h2>External services</h2><p>Links to WhatsApp and to other BOB-LIFT websites take you to external sites with their own privacy policies. This website uses analytics and advertising tracking tools to measure visits and the performance of our advertising. These tools may set cookies or similar identifiers in your browser, and you can block or delete them in your browser settings.</p><h2>Questions</h2><p>For questions about information you have sent, contact <a href="mailto:{company.email}">{company.email}</a> and identify the enquiry concerned.</p>',
        ],
        'hero' => ['eyebrow' => 'YOUR INFORMATION', 'title' => 'PRIVACY &<br>ENQUIRIES.', 'breadcrumb' => 'Privacy'],
        'seo' => ['title' => 'Privacy & Enquiry Information', 'description' => 'How this BOB-LIFT website handles enquiry information and email drafts.', 'image' => ''],
    ],
    'sections' => [
        [
            'title' => '首屏',
            'fields' => [
                ['key' => 'hero.eyebrow', 'label' => '小标题', 'type' => 'html'],
                ['key' => 'hero.title', 'label' => '标题', 'type' => 'html'],
                ['key' => 'hero.breadcrumb', 'label' => '面包屑名称', 'type' => 'text'],
            ],
        ],
        [
            'title' => '询盘模式说明',
            'fields' => [
                ['key' => 'body.submitted', 'label' => '站内提交模式正文', 'type' => 'html', 'help' => '可使用 {company.name}、{company.domain}、{company.email} 引用全站信息。'],
                ['key' => 'body.draft', 'label' => '邮件草稿模式正文', 'type' => 'html', 'help' => '可使用 {company.name}、{company.domain}、{company.email} 引用全站信息。'],
            ],
        ],
        [
            'title' => '搜索结果显示',
            'fields' => [
                ['key' => 'seo.title', 'label' => '搜索标题', 'type' => 'text'],
                ['key' => 'seo.description', 'label' => '搜索描述', 'type' => 'textarea'],
                ['key' => 'seo.image', 'label' => '分享图片', 'type' => 'image', 'preview' => 'images/share-default.jpg'],
            ],
        ],
    ],
];
