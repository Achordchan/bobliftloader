<?php
declare(strict_types=1);

/**
 * The enquiry form as a real form: Contact Form 7 sends the mail and Flamingo keeps every entry in the WordPress admin
 * (both plugins are needed).
 * The form's HTML is the theme's own (its CSS is unchanged); the theme creates the form once, so installing the two plugins
 * is all a site needs. Without them the theme keeps its email-draft form. Spam protection: a hidden field bots fill in,
 * Akismet (below), and Cloudflare Turnstile once its keys are saved in Contact → Integration.
 */

// Raised when the theme's form or notification email changes, so the form the theme made earlier gets the new one once
// (2: the HTML card; 3: no country field, the card shows the country of the sender's IP address instead; 4: the card
// redesigned in the site's colours; 5: the country in Chinese, the same text size on phones;
// 6: the sender is the site title again; the country names come from the theme's own table).
const BL_ENQUIRY_MAIL_VERSION = 6;
// Marks the notification card in the form's Mail body; only that layout is sent without CF7's automatic paragraphs.
const BL_ENQUIRY_CARD_MARKER = '<!-- bl-inquiry-card -->';

/**
 * The form CF7 renders for the theme, or 0 when CF7 or Flamingo is not active (or the form has been deleted). Flamingo is
 * required because CF7 alone keeps nothing: an enquiry whose email failed would be lost, and the privacy page promises a copy.
 */
function bl_wp_inquiry_form_id(): int
{
    if (!class_exists('WPCF7_ContactForm') || !class_exists('Flamingo_Inbound_Message')) return 0;
    $id = (int) get_option('bl_inquiry_form');
    return $id && get_post_type($id) === 'wpcf7_contact_form' && get_post_status($id) === 'publish' ? $id : 0;
}

/** An explicit global email override applies only to the theme's primary notification. Legacy plugin recipients remain intact until then. */
/** Where enquiry notifications go right now: the chosen sales email once one was chosen, else the form's own recipient. */
function bl_wp_inquiry_current_recipient(): string
{
    $saved = get_option('bl_content_global', []);
    if (is_array($saved) && (!empty($saved['_bl_email_explicit']) || (is_array($saved['company'] ?? null) && array_key_exists('email', $saved['company'])))) return bl_content_resolve('global', $saved)['company']['email'];
    $form = class_exists('WPCF7_ContactForm') && bl_inquiry_form_id() ? WPCF7_ContactForm::get_instance(bl_inquiry_form_id()) : null;
    return $form ? (string) ($form->prop('mail')['recipient'] ?? '') : '';
}

function bl_wp_inquiry_recipient(array $components, $form, $mail): array
{
    if (!$form || !$mail || (int) $form->id() !== (int) get_option('bl_inquiry_form') || $mail->name() !== 'mail') return $components;
    $saved = get_option('bl_content_global', []);
    if (is_array($saved) && (!empty($saved['_bl_email_explicit']) || (is_array($saved['company'] ?? null) && array_key_exists('email', $saved['company'])))) $components['recipient'] = bl_content_resolve('global', $saved)['company']['email'];
    return $components;
}
add_filter('wpcf7_mail_components', 'bl_wp_inquiry_recipient', 10, 3);

/** The form for one page: $atts carry the choices the page already made (product family, model, message). */
function bl_wp_inquiry_form(array $atts): string
{
    $atts['product'] = ['loader' => 'Skid steer loader', 'dumper' => 'Mini dumper', 'help' => 'Help me choose', 'service' => 'Parts & service'][$atts['product'] ?? 'loader'] ?? 'Skid steer loader';
    $form = ($id = bl_wp_inquiry_form_id()) ? WPCF7_ContactForm::get_instance($id) : null;
    return $form ? $form->form_html($atts + ['html_class' => 'bl-inquiry-form']) : '';
}

/** The form's fields. Until version 3 the sender also typed a country; $withCountry gives that form, to recognise it. */
function bl_wp_inquiry_template(bool $withCountry = false): string
{
    $country = $withCountry ? "\n" . '<label>Country / region <span>*</span>[text* country autocomplete:country-name maxlength:100 placeholder "Where will you use the equipment?"]</label>' : '';
    $privacy = esc_url(wp_make_link_relative(home_url('/privacy/')));
    return <<<HTML
<fieldset><legend>01 / YOUR EQUIPMENT</legend>
[radio product use_label_element default:shortcode_attr "Skid steer loader" "Mini dumper" "Help me choose" "Parts & service"]
[hidden model default:shortcode_attr]</fieldset>
<fieldset><legend>02 / A LITTLE ABOUT YOU</legend><div class="bl-form-grid">
<label>Your name <span>*</span>[text* name autocomplete:name maxlength:100 placeholder "Full name"]</label>
<label>Business email <span>*</span>[email* email autocomplete:email maxlength:200 placeholder "you@company.com"]</label>{$country}
<label>Company[text company autocomplete:organization maxlength:150 placeholder "Company name"]</label>
</div>
<label class="bl-message-label">Your project[textarea message rows:3 maxlength:2000 default:shortcode_attr placeholder "What work do you need to get done?"]</label>
<div class="bl-hp" aria-hidden="true"><label>Leave this field empty[text bl_website tabindex:-1 autocomplete:off]</label></div></fieldset>
<div class="bl-form-bottom"><p>Your details are used only to answer this enquiry. <br>See our <a href="{$privacy}">privacy information</a>.</p>[submit class:bl-button class:bl-button-blue "Send enquiry"]</div>
HTML;
}

/** Create the form the first time an administrator opens the admin with CF7 active. An edited form is never overwritten. */
add_action('admin_init', static function (): void {
    if (!class_exists('WPCF7_ContactForm') || !class_exists('Flamingo_Inbound_Message') || bl_wp_inquiry_form_id() || !current_user_can('manage_options')) return;
    $known = get_posts(['post_type' => 'wpcf7_contact_form', 'post_status' => 'publish', 'meta_key' => '_bl_inquiry', 'meta_value' => '1', 'numberposts' => 1, 'fields' => 'ids']);
    if ($known) {
        update_option('bl_inquiry_form', (int) $known[0]);
        return;
    }
    $form = WPCF7_ContactForm::get_template(['locale' => 'en_US', 'title' => 'BOB-LIFT enquiry']);
    $messages = $form->prop('messages');
    $messages['mail_sent_ok'] = 'Thank you. Your enquiry has been sent to the BOB-LIFT team.';
    $host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
    $form->set_properties([
        'form' => bl_wp_inquiry_template(),
        'mail' => [
            'active' => true,
            'sender' => bl_wp_inquiry_sender(),
            'recipient' => bl_company('email'),
            'additional_headers' => 'Reply-To: [name] <[email]>',
            'attachments' => '',
        ] + bl_wp_inquiry_mail(),
        'messages' => $messages,
        // Flamingo lists entries by these; the default field names of CF7's sample form do not exist here.
        'additional_settings' => "flamingo_name: \"[name]\"\nflamingo_email: \"[email]\"\nflamingo_subject: \"[_raw_product]\"",
    ]);
    $id = $form->save();
    if ($id) {
        update_post_meta($id, '_bl_inquiry', '1');
        update_option('bl_inquiry_form', (int) $id);
        update_option('bl_inquiry_mail_version', BL_ENQUIRY_MAIL_VERSION);
    }
});

// The theme lays out its own form and its notification card, so CF7 must not wrap their markup in paragraphs. Other
// forms, an edited mail and the autoresponder keep CF7's setting.
add_filter('wpcf7_autop_or_not', static function (bool $autop, $options = []): bool {
    $for = $options['for'] ?? 'form';
    if ($for === 'mail') return bl_wp_inquiry_card_mail() ? false : $autop;
    if ($for !== 'form') return $autop;
    $form = WPCF7_ContactForm::get_current();
    return $form && $form->id() === bl_wp_inquiry_form_id() ? false : $autop;
}, 10, 2);

// A field only a script fills in: anything in it means a bot filled every box.
add_filter('wpcf7_spam', static fn(bool $spam): bool => $spam || trim((string) ($_POST['bl_website'] ?? '')) !== '');

/**
 * Akismet checks the theme's form too. CF7 asks Akismet only about forms whose fields carry an akismet: option, and the
 * form saved on the site has none, so the name and email fields get theirs here, as CF7 reads the form. A field an editor
 * already marked is left as it is. Akismet's verdict files spam under Flamingo → Spam: no mail, no thank-you page.
 */
function bl_wp_inquiry_akismet_tag($tag)
{
    // CF7 6.2 hands over an array; a WPCF7_FormTag (ArrayAccess, read by value) is handled too, so the options are
    // read out and written back whole rather than appended in place.
    $role = ['name' => 'author', 'email' => 'author_email'][(string) ($tag['name'] ?? '')] ?? '';
    $options = $tag['options'] ?? null;
    if ($role === '' || !is_array($options) || preg_grep('/^akismet:/i', $options)) return $tag;
    $form = class_exists('WPCF7_ContactForm') ? WPCF7_ContactForm::get_current() : null;
    if (!$form || (int) $form->id() !== bl_wp_inquiry_form_id()) return $tag;
    $options[] = 'akismet:' . $role;
    $tag['options'] = $options;
    return $tag;
}
add_filter('wpcf7_form_tag', 'bl_wp_inquiry_akismet_tag');

/**
 * The notification the team receives: the BOB-LIFT logo, the page the enquiry came from, the product (photo, name,
 * model), the sender's details and message, where the sender is (IP country), and a button that starts the reply. The
 * card is a fixed 660 px: phones shrink it to fit the screen instead of reflowing it. Subject and body stay editable in
 * the form's Mail tab; the [bl_…] tags below fill in what CF7 cannot.
 */
function bl_wp_inquiry_mail(): array
{
    $font = "font-family:-apple-system,BlinkMacSystemFont,'PingFang SC','Microsoft YaHei','Helvetica Neue',Arial,sans-serif;";
    // The message's heading shares its line with the message, so both go when the sender wrote none.
    $heading = static fn(string $text): string => '<tr><td style="padding:28px 36px 12px;font-size:13px;font-weight:bold;letter-spacing:1px;color:#64748b;">' . $text . '</td></tr>';
    $row = static fn(string $name, string $tag): string => '<tr><td width="96" style="width:96px;padding:13px 0;border-bottom:1px solid #edf0f4;font-size:14px;color:#64748b;vertical-align:top;">' . $name . '</td><td style="padding:13px 0;border-bottom:1px solid #edf0f4;font-size:16px;color:#0b1f33;line-height:1.6;vertical-align:top;word-break:break-word;">' . $tag . '</td></tr>';
    $meta = static fn(string $name, string $tag): string => '<td width="33%" style="width:33%;padding:16px 18px;vertical-align:top;"><div style="font-size:12px;color:#64748b;padding-bottom:4px;">' . $name . '</div><div style="font-size:15px;font-weight:bold;color:#0b1f33;line-height:1.4;word-break:break-word;">' . $tag . '</div></td>';
    // Phones such as iOS Mail enlarge long blocks of text by themselves, which made the message larger than the rows;
    // the outer table turns that off. Each table row (and each cell of the strip) is one line: with "exclude blank" on, CF7 drops the line of a field
    // the sender left empty.
    $rows = implode("\n", [
        $row('姓名', '[name]'),
        $row('邮箱', '<a href="mailto:[email]" style="color:#215bdb;text-decoration:none;">[email]</a>'),
        $row('公司', '[company]'),
    ]);
    $strip = implode("\n", [$meta('来源国家', '[bl_ip_country]'), $meta('IP 地址', '[_remote_ip]'), $meta('提交时间', '[bl_time]')]);
    $body = BL_ENQUIRY_CARD_MARKER . "\n" . <<<HTML
<table width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#eef1f5;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;text-size-adjust:100%;{$font}"><tr><td align="center" style="padding:32px 0;">
<table width="660" cellspacing="0" cellpadding="0" style="width:660px;background:#ffffff;border:1px solid #e3e8ef;border-radius:10px;border-collapse:separate;overflow:hidden;text-align:left;">
<tr><td height="6" style="height:6px;background:#215bdb;font-size:0;line-height:0;border-radius:10px 10px 0 0;">&nbsp;</td></tr>
<tr><td style="padding:22px 36px;border-bottom:1px solid #edf0f4;"><table width="100%" cellspacing="0" cellpadding="0"><tr><td style="vertical-align:middle;">[bl_logo]</td><td align="right" style="vertical-align:middle;font-size:13px;color:#64748b;">[bl_time]</td></tr></table></td></tr>
<tr><td style="padding:30px 36px 0;"><div style="font-size:26px;line-height:1.3;font-weight:bold;color:#0b1f33;">新询盘通知</div><div style="padding-top:10px;font-size:15px;line-height:1.7;color:#475569;">尊敬的管理员，你有一封新询盘，内容如下，请及时跟进。</div></td></tr>
<tr><td style="padding:20px 36px 0;"><table width="100%" cellspacing="0" cellpadding="0" style="background:#f5f7fa;border-radius:8px;"><tr><td style="padding:14px 18px;"><div style="font-size:12px;color:#64748b;padding-bottom:6px;">来源页面 &nbsp;<span style="display:inline-block;padding:2px 8px;border-radius:10px;background:#e3ebfc;color:#215bdb;font-size:12px;font-weight:bold;">[bl_enquiry_kind]</span></div><div style="font-size:14px;line-height:1.6;word-break:break-all;">[bl_page_url]</div></td></tr></table></td></tr>
{$heading('产品信息')}
<tr><td style="padding:0 36px;">[bl_product_card]</td></tr>
{$heading('客户信息')}
<tr><td style="padding:0 36px;"><table width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #edf0f4;">
{$rows}
</table></td></tr>
{$heading('留言')}<tr><td style="padding:0 36px;"><div style="padding:16px 20px;background:#f5f7fa;border-radius:8px;font-size:16px;line-height:1.75;color:#0b1f33;word-break:break-word;">[message]</div></td></tr>
<tr><td style="padding:24px 36px 0;"><table width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #edf0f4;border-radius:8px;border-collapse:separate;"><tr>
{$strip}
</tr></table></td></tr>
<tr><td align="center" style="padding:32px 36px 12px;"><table cellspacing="0" cellpadding="0"><tr><td align="center" style="background:#215bdb;border-radius:8px;"><a href="mailto:[email]" style="display:inline-block;width:260px;line-height:50px;font-size:17px;font-weight:bold;color:#ffffff;text-decoration:none;text-align:center;letter-spacing:1px;">立即回复客户</a></td></tr></table></td></tr>
<tr><td align="center" style="padding:0 36px 32px;font-size:13px;line-height:1.6;color:#64748b;">也可以直接回复这封邮件，回信会发到 [email]</td></tr>
</table>
<table width="660" cellspacing="0" cellpadding="0" style="width:660px;"><tr><td align="center" style="padding:18px 36px 0;font-size:12px;line-height:1.7;color:#94a3b8;">此邮件由 [bl_site] 询盘表单自动发送 · 副本见网站后台「Flamingo → 收到的消息」</td></tr></table>
</td></tr></table>
HTML;
    return [
        'active' => true,
        'subject' => '【装载设备站】询盘飞奔而来，请及时跟进：[email]',
        'body' => $body,
        'use_html' => true,
        'exclude_blank' => true,
    ];
}

/** The plain-text mail the theme gave its form before version 2, to tell an untouched form from one an editor changed. */
const BL_ENQUIRY_MAIL_V1 = [
    'subject' => 'Website enquiry — [_raw_product]',
    'body' => "Equipment: [_raw_product]\nModel: [model]\nName: [name]\nEmail: [email]\nCountry: [country]\nCompany: [company]\n\nProject:\n[message]\n\n-- \nSent from [_site_url] on [_date] at [_time]",
];

// The HTML bodies the theme gave its form before (md5 of the trimmed body): version 2 with a country row, 3 and 4.
const BL_ENQUIRY_MAIL_EARLIER = ['7c345410a541a4ed5e5c6272d3971e19', '2c56857cde1a7dbe0e2e46d2db972231', '09a877efb12c5f681d0e55a193888211'];

/** Who the notification comes from: the site title, at the site's own domain so mail servers accept it. */
function bl_wp_inquiry_sender(): string
{
    return '[_site_title] <wordpress@' . wp_parse_url(home_url(), PHP_URL_HOST) . '>';
}

/**
 * Bring the form the theme made earlier up to date, once. Only what still is one of the theme's earlier defaults is
 * replaced: a form, body or subject an editor changed is kept (the new version can then be pasted in by hand), and who
 * receives the mail is never touched.
 */
add_action('init', static function (): void {
    if ((int) get_option('bl_inquiry_mail_version') >= BL_ENQUIRY_MAIL_VERSION || !($id = bl_wp_inquiry_form_id())) return;
    $form = WPCF7_ContactForm::get_instance($id);
    if (!$form) return;
    $same = static fn($saved, string $default): bool => trim(str_replace("\r\n", "\n", (string) $saved)) === trim($default);
    $mail = (array) $form->prop('mail');
    $new = bl_wp_inquiry_mail();
    $changes = [];
    $body = trim(str_replace("\r\n", "\n", (string) ($mail['body'] ?? '')));
    if (($body === BL_ENQUIRY_MAIL_V1['body'] && empty($mail['use_html'])) || in_array(md5($body), BL_ENQUIRY_MAIL_EARLIER, true)) {
        $changes += ['body' => $new['body'], 'use_html' => true];
    }
    if ($same($mail['subject'] ?? '', BL_ENQUIRY_MAIL_V1['subject'])) $changes['subject'] = $new['subject'];
    // Version 5 named the sender in Chinese for a while; that name goes back to the site title.
    if ($same($mail['sender'] ?? '', 'BOB-LIFT 询盘通知 <wordpress@' . wp_parse_url(home_url(), PHP_URL_HOST) . '>')) $changes['sender'] = bl_wp_inquiry_sender();
    $properties = $changes ? ['mail' => array_replace($mail, $changes)] : [];
    // The country field goes only when no mail that stays (an edited body or subject, the autoresponder) still uses it.
    $kept = [$properties['mail'] ?? $mail, (array) $form->prop('mail_2')];
    $usesCountry = (bool) array_filter($kept, static fn(array $m): bool => !empty($m['active']) && (bool) preg_match('/\[_?(?:raw_|format_)?country\b/', implode("\n", array_map('strval', array_filter($m, 'is_scalar')))));
    if (!$usesCountry && $same($form->prop('form'), bl_wp_inquiry_template(true))) $properties['form'] = bl_wp_inquiry_template();
    if ($properties) {
        $form->set_properties($properties);
        $form->save();
    }
    update_option('bl_inquiry_mail_version', BL_ENQUIRY_MAIL_VERSION);
});

/** The page an enquiry came from, for a mail: its title in bold (when known) above its address as a link. */
function bl_wp_inquiry_page_html(array $page, string $color, string $titleColor = '#0b1f33'): string
{
    $link = '<a href="' . esc_url($page['url']) . '" target="_blank" style="color:' . $color . ';text-decoration:none;word-break:break-all;">' . esc_html($page['url']) . '</a>';
    return ($page['title'] !== '' ? '<b style="color:' . $titleColor . ';">' . esc_html($page['title']) . '</b><br>' : '') . $link;
}

/** Whether CF7 is composing the theme's notification card: the form's main mail, whose body still carries the marker. */
function bl_wp_inquiry_card_mail(): bool
{
    $form = class_exists('WPCF7_ContactForm') ? WPCF7_ContactForm::get_current() : null;
    if (!$form || $form->id() !== bl_wp_inquiry_form_id() || WPCF7_Mail::get_current_template_name() !== 'mail') return false;
    return str_contains((string) (((array) $form->prop('mail'))['body'] ?? ''), BL_ENQUIRY_CARD_MARKER);
}

/** The product the sender chose: the model's photo, name and link when they wrote from a product page, else the family. */
function bl_wp_inquiry_product_card(string $family, string $model): string
{
    $product = null;
    if ($model !== '') foreach (bl_products() as $record) if (strcasecmp((string) $record['model'], $model) === 0) { $product = $record; break; }
    $pill = static fn(string $label, string $text): string => '<span style="display:inline-block;margin:0 6px 6px 0;padding:4px 10px;border-radius:6px;background:#f1f4f9;font-size:13px;color:#334155;white-space:nowrap;"><span style="color:#64748b;">' . $label . '</span> ' . esc_html($text) . '</span>';
    $pills = ($model !== '' ? $pill('型号', $model) : '') . $pill('类型', $family !== '' ? $family : '—');
    $box = static fn(string $inner): string => '<table width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e3e8ef;border-radius:8px;border-collapse:separate;"><tr>' . $inner . '</tr></table>';
    if (!$product) return $box('<td style="padding:16px 18px 10px;">' . $pills . '</td>');
    $url = esc_url($product['url']);
    $image = bl_wp_inquiry_thumbnail($product);
    $photo = $image ? '<td width="104" style="width:104px;padding:16px 0 16px 16px;vertical-align:top;"><a href="' . $url . '" target="_blank"><img src="' . esc_url($image) . '" width="104" height="104" alt="' . esc_attr($product['name']) . '" style="display:block;width:104px;height:104px;border-radius:6px;border:1px solid #edf0f4;object-fit:cover;"></a></td>' : '';
    return $box($photo . '<td style="padding:16px 18px 10px;vertical-align:top;"><a href="' . $url . '" target="_blank" style="display:block;padding-bottom:10px;font-size:17px;line-height:1.45;font-weight:bold;color:#0b1f33;text-decoration:none;">' . esc_html($product['name']) . '</a>' . $pills . '</td>');
}

/**
 * The file in the uploads folder that a stored image address points to (the media library keeps it as a path from the site
 * root, "/wp-content/uploads/2026/10/x.webp", or as a full address), or '' for any other address.
 */
function bl_wp_inquiry_uploads_file(string $address): string
{
    $uploads = wp_upload_dir();
    $base = rtrim((string) wp_parse_url((string) $uploads['baseurl'], PHP_URL_PATH), '/');
    $host = wp_parse_url($address, PHP_URL_HOST);
    if ($base === '' || ($host && $host !== wp_parse_url((string) $uploads['baseurl'], PHP_URL_HOST))) return '';
    $path = rawurldecode((string) wp_parse_url($address, PHP_URL_PATH));
    if (!str_starts_with($path, $base . '/') || str_contains($path, '..')) return '';
    return trailingslashit((string) $uploads['basedir']) . substr($path, strlen($base) + 1);
}

/**
 * An image address a mail program can open: one kept as a path from the site root gets the site's scheme and host in front of
 * it (not the home URL, which on a site in a subfolder already ends with that folder, and the path names it too).
 */
function bl_wp_inquiry_absolute_url(string $address): string
{
    if (!str_starts_with($address, '/') || str_starts_with($address, '//')) return $address;
    $home = (array) wp_parse_url(home_url());
    if (empty($home['scheme']) || empty($home['host'])) return $address;
    return $home['scheme'] . '://' . $home['host'] . (isset($home['port']) ? ':' . $home['port'] : '') . $address;
}

/**
 * A square of the product's photo (twice its 104 px size, for sharp screens ), from its featured image, else the image the
 * product record names (the media library's, or the theme's own catalogue image), saved once as a JPEG in the uploads:
 * product photos are WebP, which some desktop mail programs do not show. Falls back to the image as it is, with its full
 * address: a path from the site root opens in the browser but is a broken picture in a mail.
 */
function bl_wp_inquiry_thumbnail(array $product): string
{
    $post = get_page_by_path((string) $product['slug'], OBJECT, 'bl_product');
    $attachment = $post ? (int) get_post_thumbnail_id($post) : 0;
    $file = $attachment ? (string) get_attached_file($attachment) : '';
    if (!$file && $post) {
        $record = get_post_meta($post->ID, '_bl_record', true);
        $stored = is_array($record) ? (string) ($record['image'] ?? '') : '';
        if (str_starts_with($stored, 'images/') && !str_contains($stored, '..')) $file = get_template_directory() . '/assets/' . $stored;
        elseif ($stored !== '') $file = bl_wp_inquiry_uploads_file($stored);
    }
    $fallback = bl_wp_inquiry_absolute_url((string) $product['image']);
    if (!$file || !is_file($file)) return $fallback;
    $uploads = wp_upload_dir();
    $name = 'bl-mail/' . substr(md5($file . filemtime($file)), 0, 12) . '-208.jpg';
    $path = trailingslashit($uploads['basedir']) . $name;
    if (!is_file($path)) {
        $editor = wp_get_image_editor($file);
        if (is_wp_error($editor) || is_wp_error($editor->resize(208, 208, true)) || is_wp_error($editor->save($path, 'image/jpeg'))) return $fallback;
    }
    return trailingslashit($uploads['baseurl']) . $name;
}

/** The country the sender's IP address points to, in Chinese (e.g. "加拿大"); empty when it cannot be told. */
function bl_wp_ip_country(string $ip): string
{
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return '';
    $key = 'bl_ip_country_' . md5($ip);
    $code = get_transient($key);
    if ($code === false) {
        $response = wp_remote_get('https://ipinfo.io/' . rawurlencode($ip) . '/country', ['timeout' => 3]);
        $text = strtoupper(trim((string) wp_remote_retrieve_body($response)));
        $code = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200 && preg_match('/^[A-Z]{2}$/', $text) ? $text : '';
        set_transient($key, $code, $code ? WEEK_IN_SECONDS : HOUR_IN_SECONDS);
    }
    if ($code === '') return '';
    // The theme's own table: the server's intl may lack Chinese names and fall back to English. Unknown codes show as is.
    static $names = null;
    $names ??= require __DIR__ . '/country-names-zh.php';
    return $names[$code] ?? $code;
}

// Ad and analytics tracking parameters. They are left out of the page an enquiry came from (the attribution block lists the
// advert by itself), so the address says which page it was.
const BL_ENQUIRY_TRACKING = '/^(utm_[a-z_]+|gclid|gclsrc|gbraid|wbraid|dclid|gad_[a-z_]+|fbclid|msclkid|ttclid|twclid|li_fat_id|srsltid|_gl)$/i';

/**
 * The page an enquiry was sent from: its address without tracking parameters (a site-relative one gets the site's own
 * origin) and the page's title as the site titles it, empty for an address this site does not serve.
 */
function bl_wp_inquiry_page(string $url): array
{
    // A path from the site root already holds any folder the site lives in, so it takes only the scheme and host.
    $url = bl_wp_inquiry_absolute_url(trim($url));
    $hash = '';
    if (($at = strpos($url, '#')) !== false) [$url, $hash] = [substr($url, 0, $at), substr($url, $at)];
    if (($at = strpos($url, '?')) !== false) {
        $kept = array_filter(explode('&', substr($url, $at + 1)), static fn(string $pair): bool => $pair !== '' && !preg_match(BL_ENQUIRY_TRACKING, rawurldecode(explode('=', $pair, 2)[0])));
        $url = substr($url, 0, $at) . ($kept ? '?' . implode('&', $kept) : '');
    }
    $url .= $hash;
    $title = '';
    $host = static fn($address): string => preg_replace('/^www\./', '', strtolower((string) wp_parse_url((string) $address, PHP_URL_HOST)));
    if ($url !== '' && $host($url) === $host(home_url('/'))) {
        // An address that names a post by its ID (?p=9, ?page_id=9) is that post, whatever its path looks like.
        $byId = (bool) preg_match('/(?:^|&)(?:p|page_id|attachment_id)=\d/', (string) wp_parse_url($url, PHP_URL_QUERY));
        $route = $byId ? ['status' => 404] : bl_resolve_route((string) (wp_parse_url($url, PHP_URL_PATH) ?: '/'));
        if ($route['status'] === 200) $title = bl_plain_title((string) $route['title']);
        elseif (($id = url_to_postid($url)) > 0) $title = bl_plain_title(get_the_title($id));
    }
    return ['url' => $url, 'title' => $title];
}

// The DingTalk plugin heads each message with the site, and names the page an enquiry came from the way this theme does.
add_filter('achord_dingtalk_site_name', static fn($name) => trim((string) $name) !== '' ? $name : '装载设备站');
add_filter('achord_dingtalk_page_title', static fn($title, $url) => trim((string) $title) !== '' ? $title : bl_wp_inquiry_page((string) $url)['title'], 10, 2);

// The notification's own tags. CF7 asks for them only when the mail names them, so other forms are not affected.
add_filter('wpcf7_special_mail_tags', static function ($output, string $name, bool $html) {
    if (!in_array($name, ['bl_page_link', 'bl_page_url', 'bl_enquiry_kind', 'bl_logo', 'bl_site', 'bl_time', 'bl_product_card', 'bl_ip_country'], true)) return $output;
    if ($name === 'bl_site') return (string) wp_parse_url(home_url(), PHP_URL_HOST);
    if ($name === 'bl_logo') {
        // Mail clients have no page to resolve a root-relative address against, so it gets the site's own origin.
        $src = bl_brand_logo();
        if (str_starts_with($src, '//')) $src = (wp_parse_url(home_url(), PHP_URL_SCHEME) ?: 'https') . ':' . $src;
        elseif (str_starts_with($src, '/')) {
            $home = wp_parse_url(home_url());
            $src = ($home['scheme'] ?? 'https') . '://' . ($home['host'] ?? '') . (isset($home['port']) ? ':' . $home['port'] : '') . $src;
        }
        $logo = '<img src="' . esc_url($src) . '" width="112" height="45" alt="BOB-LIFT" style="display:block;width:112px;height:45px;border:0;">';
        return $html ? $logo : 'BOB-LIFT';
    }
    $submission = WPCF7_Submission::get_instance();
    if (!$submission) return '';
    // In the site's time zone, written the same way whatever the admin's date format.
    if ($name === 'bl_time') return wp_date('Y-m-d H:i', (int) ($submission->get_meta('timestamp') ?: time()));
    if ($name === 'bl_ip_country') {
        $ip = (string) $submission->get_meta('remote_ip');
        $country = $ip === '' ? '' : (bl_wp_ip_country($ip) ?: '未能识别');
        return $html ? esc_html($country) : $country;
    }
    $model = trim((string) $submission->get_posted_string('model'));
    if ($name === 'bl_enquiry_kind') return $model !== '' ? '产品询盘' : '询盘表单';
    if ($name === 'bl_page_url') {
        $page = bl_wp_inquiry_page((string) $submission->get_meta('url'));
        if (!$html) return trim($page['title'] . ' ' . $page['url']);
        return bl_wp_inquiry_page_html($page, '#215bdb');
    }
    // Kept for a Mail body an editor built on the version 2 card.
    if ($name === 'bl_page_link') {
        $url = (string) $submission->get_meta('url');
        $kind = $model !== '' ? '（产品询盘）' : '（询盘表单）';
        if (!$html) return $url . $kind;
        return '<a href="' . esc_url($url) . '" target="_blank" style="color:#1995f6;">' . esc_html($url) . '</a>' . $kind;
    }
    $family = trim((string) $submission->get_posted_string('product'));
    return $html ? bl_wp_inquiry_product_card($family, $model) : trim($family . ' ' . $model);
}, 10, 3);

// Keep the line breaks of the sender's message in the HTML email.
add_filter('wpcf7_mail_tag_replaced_textarea', static function ($replaced, $submitted, bool $html) {
    return $html && is_string($replaced) && bl_wp_inquiry_card_mail() ? str_replace(["\r", "\n"], '', nl2br($replaced, false)) : $replaced;
}, 10, 3);

/**
 * The thank-you page the form leads to once the enquiry is sent (/thank-you/; words in 网站内容 → 询盘成功页). WordPress
 * needs a page at that address; it is made once, and kept out of the sitemaps since the page is for people who just wrote.
 */
function bl_wp_thanks_page(): void
{
    if (get_option('bl_thanks_page') || !($token = bl_lock_acquire('thanks_page', 1))) return;
    try {
        $page = get_page_by_path('thank-you');
        $id = $page ? $page->ID : wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Thank you', 'post_name' => 'thank-you'], true);
        if (is_wp_error($id) || !$id) return;
        if (get_post_field('post_name', (int) $id) !== 'thank-you') { wp_delete_post((int) $id, true); $id = -1; }
        update_option('bl_thanks_page', (int) $id);
    } finally {
        bl_lock_release('thanks_page', $token);
    }
}
add_action('init', 'bl_wp_thanks_page', 20);

add_filter('wp_sitemaps_posts_query_args', static function (array $args, string $postType): array {
    if ($postType === 'page' && ($id = (int) get_option('bl_thanks_page')) > 0) $args['post__not_in'] = array_merge($args['post__not_in'] ?? [], [$id]);
    return $args;
}, 10, 2);
add_filter('jetpack_sitemap_skip_post', static fn($skip, $post) => $skip || (is_object($post) && (int) $post->ID === (int) get_option('bl_thanks_page')), 10, 2);
