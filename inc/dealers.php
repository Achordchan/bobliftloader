<?php
declare(strict_types=1);

/**
 * The dealer page (/dealers/, words in content-dealers.php) and its application form. The form goes to the inquiry plugin
 * like every enquiry, marked as a dealer application (bl_lead=dealer) so the mail, DingTalk and the 询盘 list name it;
 * see inc/wp-inquiry.php for that side.
 */

// What the applicant would sell: the value sent => the words the sales team reads.
const BL_DEALER_INTERESTS = ['Mini skid steer loaders' => '滑移装载机', 'Mini dumpers' => '迷你翻斗车', 'Both ranges' => '两类都要'];
// What kind of business applies: the value sent => the words the sales team reads.
const BL_DEALER_BUSINESSES = ['Equipment dealer' => '设备经销商', 'Rental company' => '设备租赁', 'Importer / distributor' => '进口商 / 分销商', 'Other' => '其他'];

/** The page's {tokens}, filled from the terms and the published range so the page never states figures the rest of the site has changed. */
function bl_dealer_tokens(): array
{
    $terms = bl_support_terms();
    $loaders = bl_family_ranges('loader');
    $dumpers = bl_family_ranges('dumper');
    $warranty = trim($terms['warranty']['value'] . "\u{2011}" . rtrim($terms['warranty']['unit'], 's'));
    // The minimum order comes from the same terms as Support, so a changed minimum changes every sentence that states it.
    return bl_business_variables($terms) + [
        '{warranty}' => $warranty,
        '{lead}' => trim($terms['lead']['value'] . ' ' . $terms['lead']['unit']),
        '{loader_count}' => (string) $loaders['count'],
        '{loader_load}' => $loaders['loaded'] ? bl_load_range($loaders['load']) : '',
        '{dumper_count}' => (string) $dumpers['count'],
        '{dumper_load}' => $dumpers['loaded'] ? bl_load_range($dumpers['load']) : '',
    ];
}

/** A line of the page's copy, escaped, with its {tokens} filled in. */
function bl_dealer_text(string $text): string
{
    return bl_escape(strtr($text, bl_dealer_tokens()));
}

/** The questions with answers, for the page and its FAQPage data. */
function bl_dealer_faq(): array
{
    $items = bl_editable('dealers')['faq']['items'] ?? [];
    $out = [];
    foreach (is_array($items) ? $items : [] as $item) {
        if (!is_array($item) || trim((string) ($item['q'] ?? '')) === '' || trim((string) ($item['a'] ?? '')) === '') continue;
        $out[] = ['q' => trim((string) $item['q']), 'a' => strtr(trim((string) $item['a']), bl_dealer_tokens())];
    }
    return $out;
}

/**
 * The application form. With the inquiry plugin it is sent and stored by the site, like every enquiry; the standalone preview
 * draws the same form without sending it. A WordPress site without the plugin gets an email link instead.
 */
function bl_dealer_form(): string
{
    $live = bl_inquiry_live();
    if (bl_is_wordpress() && !$live) {
        return '<p class="bl-dealer-fallback">Email your company name, website and the countries you sell in to <a href="' . bl_escape(bl_mailto(bl_company('email'))) . '?subject=' . rawurlencode('Dealer application') . '">' . bl_escape(bl_company('email')) . '</a>.</p>';
    }
    $radios = static function (string $name, array $choices, string $checked): string {
        $html = '';
        foreach (array_keys($choices) as $value) $html .= '<span class="bl-radio-item"><label><input type="radio" name="' . $name . '" value="' . bl_escape($value) . '"' . ($value === $checked ? ' checked' : '') . '><span class="bl-radio-label">' . bl_escape($value) . '</span></label></span>';
        return '<span class="bl-radio">' . $html . '</span>';
    };
    $field = static fn(string $label, string $input): string => '<label>' . $label . $input . '</label>';
    $hidden = $live ? achord_inquiry_hidden_fields(['source' => 'Dealer application', 'language' => bl_lang()]) . '<input type="hidden" name="bl_lead" value="dealer">' : '';
    $turnstile = $live ? achord_inquiry_turnstile_widget('bl-turnstile', true) : '';
    $action = $live ? ' method="post" action="' . bl_escape(achord_inquiry_action_url()) . '" data-nonce-url="' . bl_escape(admin_url('admin-ajax.php?action=bl_inquiry_nonce')) . '"' : ' action="#" onsubmit="return false"';
    return '<form class="bl-inquiry-form bl-dealer-form"' . $action . '>'
        . '<fieldset><legend>01 / WHAT YOU WOULD SELL</legend>' . $radios('product', BL_DEALER_INTERESTS, 'Both ranges') . '</fieldset>'
        . '<fieldset><legend>02 / YOUR BUSINESS</legend>' . $radios('business', BL_DEALER_BUSINESSES, 'Equipment dealer')
        . '<div class="bl-form-grid">'
        . $field('Company <span>*</span>', '<input name="company" required maxlength="150" autocomplete="organization" placeholder="Company name">')
        . $field('Countries you sell in <span>*</span>', '<input name="market" required maxlength="150" placeholder="For example: Australia, New Zealand">')
        . $field('Website', '<input type="url" name="website" maxlength="200" inputmode="url" autocomplete="url" placeholder="https://">')
        . '</div></fieldset>'
        . '<fieldset><legend>03 / A LITTLE ABOUT YOU</legend><div class="bl-form-grid">'
        . $field('Your name <span>*</span>', '<input name="name" required maxlength="100" autocomplete="name" placeholder="Full name">')
        . $field('Business email <span>*</span>', '<input type="email" name="email" required maxlength="200" autocomplete="email" placeholder="you@company.com">')
        . $field('WhatsApp', '<input type="tel" name="phone" maxlength="40" autocomplete="tel" inputmode="tel" placeholder="+ country code and number">')
        . '</div><label class="bl-message-label">About your business<textarea name="message" rows="3" maxlength="2000" placeholder="What you sell today, who your customers are and which machines they ask for"></textarea></label>'
        . $hidden . '</fieldset>' . $turnstile
        . '<div class="bl-form-bottom"><p>Your details are used only to answer this application. <br>See our <a href="' . bl_escape(bl_url('/privacy/')) . '">privacy information</a>.</p><input class="bl-inquiry-submit bl-button bl-button-blue" type="submit" value="Send application"></div>'
        . '<p class="bl-form-status" role="status" aria-live="polite" hidden></p></form>';
}
