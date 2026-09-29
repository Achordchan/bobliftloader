<?php
declare(strict_types=1);

/**
 * The enquiry form as a real form: Contact Form 7 sends the mail and Flamingo keeps every entry in the WordPress admin.
 * The form's HTML is the theme's own (its CSS is unchanged); the theme creates the form once, so installing the two plugins
 * is all a site needs. Without them the theme keeps its email-draft form. Spam protection (Cloudflare Turnstile) is
 * switched on in Contact → Integration with the site's own keys.
 */

const BL_ENQUIRY_EMAIL = 'info@boblift.com';

/** The form CF7 renders for the theme, or 0 when CF7 is not active (or its form has been deleted). */
function bl_wp_inquiry_form_id(): int
{
    if (!class_exists('WPCF7_ContactForm')) return 0;
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
    if (!class_exists('WPCF7_ContactForm') || bl_wp_inquiry_form_id() || !current_user_can('manage_options')) return;
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
            'subject' => 'Website enquiry — [_raw_product]',
            'sender' => '[_site_title] <wordpress@' . $host . '>',
            'recipient' => BL_ENQUIRY_EMAIL,
            'body' => "Equipment: [_raw_product]\nModel: [model]\nName: [name]\nEmail: [email]\nCountry: [country]\nCompany: [company]\n\nProject:\n[message]\n\n-- \nSent from [_site_url] on [_date] at [_time]",
            'additional_headers' => 'Reply-To: [name] <[email]>',
            'attachments' => '',
            'use_html' => false,
            'exclude_blank' => true,
        ],
        'messages' => $messages,
        // Flamingo lists entries by these; the default field names of CF7's sample form do not exist here.
        'additional_settings' => "flamingo_name: \"[name]\"\nflamingo_email: \"[email]\"\nflamingo_subject: \"[_raw_product]\"",
    ]);
    $id = $form->save();
    if ($id) {
        update_post_meta($id, '_bl_inquiry', '1');
        update_option('bl_inquiry_form', (int) $id);
    }
});

// The theme lays the form out itself: no paragraph wrapping of its markup and no CF7 sample styling of the fields.
add_filter('wpcf7_autop_or_not', '__return_false');

// A field only a script fills in: anything in it means a bot filled every box.
add_filter('wpcf7_spam', static fn(bool $spam): bool => $spam || trim((string) ($_POST['bl_website'] ?? '')) !== '');
