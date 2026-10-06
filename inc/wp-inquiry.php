<?php
declare(strict_types=1);

/**
 * The enquiry form as a real form: Contact Form 7 sends the mail and Flamingo keeps every entry in the WordPress admin
 * (both plugins are needed).
 * The form's HTML is the theme's own (its CSS is unchanged); the theme creates the form once, so installing the two plugins
 * is all a site needs. Without them the theme keeps its email-draft form. Spam protection (Cloudflare Turnstile) is
 * switched on in Contact → Integration with the site's own keys.
 */

const BL_ENQUIRY_EMAIL = 'info@boblift.com';
// Raised when the theme's notification email changes, so the form the theme made earlier gets the new one once.
const BL_ENQUIRY_MAIL_VERSION = 2;

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

/** The form for one page: $atts carry the choices the page already made (product family, model, message). */
function bl_wp_inquiry_form(array $atts): string
{
    $atts['product'] = ['loader' => 'Skid steer loader', 'dumper' => 'Mini dumper', 'help' => 'Help me choose', 'service' => 'Parts & service'][$atts['product'] ?? 'loader'] ?? 'Skid steer loader';
    $form = ($id = bl_wp_inquiry_form_id()) ? WPCF7_ContactForm::get_instance($id) : null;
    return $form ? $form->form_html($atts + ['html_class' => 'bl-inquiry-form']) : '';
}

function bl_wp_inquiry_template(): string
{
    $privacy = esc_url(wp_make_link_relative(home_url('/privacy/')));
    return <<<HTML
<fieldset><legend>01 / YOUR EQUIPMENT</legend>
[radio product use_label_element default:shortcode_attr "Skid steer loader" "Mini dumper" "Help me choose" "Parts & service"]
[hidden model default:shortcode_attr]</fieldset>
<fieldset><legend>02 / A LITTLE ABOUT YOU</legend><div class="bl-form-grid">
<label>Your name <span>*</span>[text* name autocomplete:name maxlength:100 placeholder "Full name"]</label>
<label>Business email <span>*</span>[email* email autocomplete:email maxlength:200 placeholder "you@company.com"]</label>
<label>Country / region <span>*</span>[text* country autocomplete:country-name maxlength:100 placeholder "Where will you use the equipment?"]</label>
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
            'sender' => '[_site_title] <wordpress@' . $host . '>',
            'recipient' => BL_ENQUIRY_EMAIL,
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

// The theme lays out its own form and its notification email, so CF7 must not wrap their markup in paragraphs; other
// forms keep their setting.
add_filter('wpcf7_autop_or_not', static function (bool $autop, $options = []): bool {
    if (!in_array($options['for'] ?? 'form', ['form', 'mail'], true)) return $autop;
    $form = WPCF7_ContactForm::get_current();
    return $form && $form->id() === bl_wp_inquiry_form_id() ? false : $autop;
}, 10, 2);

// A field only a script fills in: anything in it means a bot filled every box.
add_filter('wpcf7_spam', static fn(bool $spam): bool => $spam || trim((string) ($_POST['bl_website'] ?? '')) !== '');

/**
 * The notification the team receives: a card with the page the enquiry came from, the product (photo, name, model) and
 * the sender's details, with a button that starts the reply. Subject and body stay editable in the form's Mail tab; the
 * [bl_…] tags below fill in what CF7 cannot.
 */
function bl_wp_inquiry_mail(): array
{
    $label = 'padding:10px 16px 10px 10px;text-align:right;vertical-align:top;font-weight:bold;font-size:14px;width:120px;color:#333333;';
    $value = 'padding:10px 16px 10px 0;vertical-align:top;font-size:14px;color:#333333;line-height:1.6;word-break:break-word;';
    $row = static fn(string $name, string $tag): string => "<tr><td width=\"120\" style=\"{$label}\">{$name}</td><td style=\"{$value}\">{$tag}</td></tr>";
    // Each row is one line: with "exclude blank" on, CF7 drops the line of a field the sender left empty.
    $rows = implode("\n", [
        $row('Name', '[name]'),
        $row('E-mail', '<a href="mailto:[email]" style="color:#1995f6;text-decoration:none;">[email]</a>'),
        $row('Company Name', '[company]'),
        $row('Country / region', '[country]'),
        $row('Message', '[message]'),
        $row('IP:', '[_remote_ip]'),
        $row('来源国家:', '[bl_ip_country]'),
    ]);
    $body = <<<HTML
<table width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#ffffff;"><tr><td style="padding:16px 8px;">
<table align="center" width="660" cellspacing="0" cellpadding="0" style="width:100%;max-width:660px;border:1px solid #d8dde6;border-collapse:separate;background:#ffffff;border-radius:3px;font-family:Arial,'Microsoft YaHei',sans-serif;color:#333333;">
<tr><td height="8" style="background:#1995f6;height:8px;font-size:0;line-height:0;border-radius:3px 3px 0 0;">&nbsp;</td></tr>
<tr><td style="padding:20px 20px 0;font-size:20px;text-align:center;"><div style="border-bottom:1px solid #d8dde6;padding-bottom:20px;">新询盘通知</div></td></tr>
<tr><td style="padding:20px;font-size:16px;line-height:1.6;">尊敬的管理员，<br>你有一封新询盘来自页面地址为<br>[bl_page_link]，内容如下：</td></tr>
<tr><td style="padding:0 20px;">
<table width="100%" cellspacing="0" cellpadding="0" style="width:100%;border:1px solid #d8dde6;background:#f4f6f9;">
<tr><td width="120" style="padding:20px 16px 10px 10px;text-align:right;vertical-align:top;font-weight:bold;font-size:14px;width:120px;color:#333333;">产品信息</td><td style="padding:20px 16px 10px 0;vertical-align:top;font-size:14px;">[bl_product_card]</td></tr>
{$rows}
<tr><td colspan="2" style="height:10px;font-size:0;line-height:0;">&nbsp;</td></tr>
</table>
</td></tr>
<tr><td align="center" style="padding:24px 20px;"><table cellspacing="0" cellpadding="0"><tr><td align="center" style="background:#1995f6;border-radius:3px;"><a href="mailto:[email]" style="display:inline-block;width:200px;line-height:40px;font-size:18px;color:#ffffff;text-decoration:none;text-align:center;">立即回复</a></td></tr></table></td></tr>
</table>
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

/** Give the form the theme made before this email existed the new subject and body, once; who receives it is kept. */
add_action('init', static function (): void {
    if ((int) get_option('bl_inquiry_mail_version') >= BL_ENQUIRY_MAIL_VERSION || !($id = bl_wp_inquiry_form_id())) return;
    $form = WPCF7_ContactForm::get_instance($id);
    if (!$form) return;
    $form->set_properties(['mail' => array_replace((array) $form->prop('mail'), bl_wp_inquiry_mail())]);
    $form->save();
    update_option('bl_inquiry_mail_version', BL_ENQUIRY_MAIL_VERSION);
});

/** Whether CF7 is composing mail for the theme's own form. */
function bl_wp_inquiry_mail_current(): bool
{
    $form = class_exists('WPCF7_ContactForm') ? WPCF7_ContactForm::get_current() : null;
    return $form && $form->id() === bl_wp_inquiry_form_id();
}

/** The product the sender chose: the model's photo, name and link when they wrote from a product page, else the family. */
function bl_wp_inquiry_product_card(string $family, string $model): string
{
    $product = null;
    if ($model !== '') foreach (bl_products() as $record) if (strcasecmp((string) $record['model'], $model) === 0) { $product = $record; break; }
    $line = static fn(string $label, string $text): string => '<p style="margin:0 0 4px;font-size:14px;color:#333333;"><span>' . $label . '</span>' . esc_html($text) . '</p>';
    $details = $line('Type: ', $family !== '' ? $family : '—') . ($model !== '' ? $line('Model: ', $model) : '') . $line('Brand: ', 'BOB-LIFT');
    if (!$product) return $details;
    $url = esc_url($product['url']);
    $name = esc_html($product['name']);
    $image = bl_wp_inquiry_thumbnail($product);
    // Two inline blocks: side by side on a wide screen, the details under the photo on a phone.
    $photo = $image ? '<div style="display:inline-block;vertical-align:top;width:80px;margin:0 14px 10px 0;"><a href="' . $url . '" target="_blank"><img src="' . esc_url($image) . '" width="80" height="80" alt="' . esc_attr($product['name']) . '" style="display:block;width:80px;height:80px;border:1px solid #eeeeee;border-radius:3px;object-fit:cover;"></a></div>' : '';
    return $photo . '<div style="display:inline-block;vertical-align:top;max-width:280px;"><p style="margin:0 0 6px;font-size:14px;line-height:1.7;"><a href="' . $url . '" target="_blank" style="color:#1995f6;text-decoration:none;">' . $name . '</a></p>' . $details . '</div>';
}

/**
 * An 80 px square of the product's featured image, saved once as a JPEG beside the uploads: product photos are WebP,
 * which some desktop mail programs do not show. Falls back to the image as it is.
 */
function bl_wp_inquiry_thumbnail(array $product): string
{
    $post = get_page_by_path((string) $product['slug'], OBJECT, 'bl_product');
    $attachment = $post ? (int) get_post_thumbnail_id($post) : 0;
    $file = $attachment ? get_attached_file($attachment) : '';
    if (!$file || !is_file($file)) return (string) $product['image'];
    $uploads = wp_upload_dir();
    $name = 'bl-mail/' . $attachment . '-' . substr(md5($file . filemtime($file)), 0, 8) . '-160.jpg';
    $path = trailingslashit($uploads['basedir']) . $name;
    if (!is_file($path)) {
        $editor = wp_get_image_editor($file);
        if (is_wp_error($editor) || is_wp_error($editor->resize(160, 160, true)) || is_wp_error($editor->save($path, 'image/jpeg'))) return (string) $product['image'];
    }
    return trailingslashit($uploads['baseurl']) . $name;
}

/** The country the sender's IP address points to, in English (e.g. "Canada"); empty when it cannot be told. */
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
    $name = class_exists('Locale') ? (string) Locale::getDisplayRegion('-' . $code, 'en') : '';
    return $name !== '' && $name !== $code ? $name . ' (' . $code . ')' : $code;
}

// The notification's own tags. CF7 asks for them only when the mail names them, so other forms are not affected.
add_filter('wpcf7_special_mail_tags', static function ($output, string $name, bool $html) {
    if (!in_array($name, ['bl_page_link', 'bl_product_card', 'bl_ip_country'], true)) return $output;
    $submission = WPCF7_Submission::get_instance();
    if (!$submission) return '';
    if ($name === 'bl_ip_country') {
        $country = bl_wp_ip_country((string) $submission->get_meta('remote_ip'));
        return $html ? esc_html($country) : $country;
    }
    $model = trim((string) $submission->get_posted_string('model'));
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
    return $html && is_string($replaced) && bl_wp_inquiry_mail_current() ? str_replace(["\r", "\n"], '', nl2br($replaced, false)) : $replaced;
}, 10, 3);
