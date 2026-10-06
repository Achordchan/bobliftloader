<?php
/** B 回复优先: the sender's name large at the top with the reply button right below, then the message, product and trace. */
return static function (array $d): string {
    $f = "font-family:-apple-system,BlinkMacSystemFont,'PingFang SC','Hiragino Sans GB','Microsoft YaHei','Helvetica Neue',Arial,sans-serif;";
    $mono = "font-family:Menlo,Consolas,'SFMono-Regular','Courier New',monospace;";
    $e = 'esc_html';
    $company = $d['company'] !== ''
        ? '<div style="padding-top:2px;font-size:16px;line-height:26px;color:#5d6b7c;">' . $e($d['company']) . '</div>'
        : '<div style="padding-top:2px;font-size:16px;line-height:26px;color:#a3adb9;">未填写公司</div>';
    $message = $d['message'] !== ''
        ? '<div style="font-size:16px;line-height:27px;color:#0b1f33;word-break:break-word;">' . bl_wp_inquiry_text($d['message']) . '</div>'
        : '<div style="font-size:16px;line-height:27px;color:#a3adb9;">客户未留言</div>';
    if ($d['product']) {
        $p = $d['product'];
        $photo = $p['image'] ? '<td width="104" valign="top" style="padding-right:20px;"><img src="' . esc_url($p['image']) . '" width="104" height="104" alt="' . esc_attr($p['name']) . '" style="display:block;width:104px;height:104px;border-radius:12px;"></td>' : '';
        $product = '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>' . $photo . '<td valign="middle" style="' . $f . '">'
            . '<div style="font-size:17px;font-weight:700;line-height:25px;color:#0b1f33;">' . $e($p['name']) . '</div>'
            . '<div style="padding-top:4px;font-size:14px;line-height:22px;color:#5d6b7c;"><span style="white-space:nowrap;">型号 ' . $e($d['model']) . '</span> · <span style="white-space:nowrap;">' . $e($d['type']) . '</span></div></td></tr></table>';
    } else {
        $product = '<div style="' . $f . 'font-size:16px;line-height:26px;color:#0b1f33;">询盘类型：<b>' . $e($d['type']) . '</b>' . ($d['model'] !== '' ? '&nbsp;&nbsp;·&nbsp;&nbsp;型号 ' . $e($d['model']) : '') . '</div>';
    }
    $source = $e($d['kind']) . ($d['url'] !== '' ? ' · <a href="' . esc_url($d['url']) . '" style="color:#5d6b7c;text-decoration:underline;word-break:break-all;">' . $e($d['url']) . '</a>' : '');
    // Label rows rather than three columns, which a phone fitting the mail to its screen squeezed until words broke.
    $cell = static fn(string $label, string $value, string $font): string => '<tr><td width="96" valign="top" style="padding:5px 0;' . $f . 'font-size:13px;line-height:22px;color:#5d6b7c;">' . $label . '</td><td valign="top" style="padding:5px 0;' . $font . 'font-size:15px;line-height:22px;color:#0b1f33;">' . $value . '</td></tr>';
    $trace = $cell('来源国家', $e($d['country'] !== '' ? $d['country'] : '未知'), $f) . ($d['ip'] !== '' ? $cell('IP 地址', $e($d['ip']), $mono) : '') . $cell('提交时间', $e($d['time']), $mono);
    $logo = esc_url($d['logo']);
    $reply = esc_url($d['reply'], ['mailto']);
    $mailto = esc_url('mailto:' . $d['email'], ['mailto']);

    return <<<HTML
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:#eceff4;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;text-size-adjust:100%;">
<tr><td align="center" style="padding:32px 12px;">
<table role="presentation" width="660" cellpadding="0" cellspacing="0" border="0" style="width:660px;background:#ffffff;border-radius:16px;border-collapse:separate;text-align:left;">
<tr><td style="padding:30px 40px 0;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td valign="middle"><img src="{$logo}" width="112" height="45" alt="BOB-LIFT" style="display:block;width:112px;height:45px;border:0;"></td>
<td align="right" style="{$f}font-size:13px;color:#5d6b7c;white-space:nowrap;">{$e($d['time'])}</td></tr></table></td></tr>
<tr><td style="padding:36px 40px 0;{$f}">
<div style="font-size:15px;font-weight:700;color:#215bdb;">新询盘通知</div>
<div style="padding-top:6px;font-size:16px;line-height:26px;color:#5d6b7c;">你好，管理员，有一位新客户想和你聊聊：</div>
<div style="padding-top:20px;font-size:34px;font-weight:800;line-height:42px;color:#0b1f33;word-break:break-word;">{$e($d['name'])}</div>
<div style="padding-top:6px;font-size:16px;line-height:26px;"><a href="{$mailto}" style="color:#215bdb;text-decoration:none;">{$e($d['email'])}</a></div>
{$company}
</td></tr>
<tr><td style="padding:26px 40px 0;"><table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" bgcolor="#215bdb" style="background:#215bdb;border-radius:999px;">
<a href="{$reply}" style="display:block;padding:15px 32px;{$f}font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:999px;white-space:nowrap;">立即回复客户 &rarr;</a></td></tr></table>
<div style="padding-top:10px;{$f}font-size:14px;line-height:22px;color:#5d6b7c;">也可以直接回复这封邮件</div></td></tr>
<tr><td style="padding:32px 40px 0;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td bgcolor="#f2f5fb" style="background:#f2f5fb;border-radius:14px;padding:22px 24px;{$f}">
<div style="padding-bottom:10px;font-size:13px;font-weight:700;color:#5d6b7c;">客户留言</div>
{$message}</td></tr></table></td></tr>
<tr><td style="padding:32px 40px 0;{$f}">
<div style="padding-bottom:14px;font-size:13px;font-weight:700;color:#5d6b7c;">询问的产品</div>
{$product}
<div style="padding-top:16px;font-size:14px;line-height:22px;color:#5d6b7c;">{$source}</div>
</td></tr>
<tr><td style="padding:28px 40px 0;"><div style="border-top:1px solid #e6eaf0;font-size:0;line-height:0;">&nbsp;</div></td></tr>
<tr><td style="padding:17px 40px 29px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">{$trace}</table></td></tr>
</table>
<table role="presentation" width="660" cellpadding="0" cellspacing="0" border="0" style="width:660px;"><tr>
<td align="center" style="padding:20px 40px 0;{$f}font-size:12px;line-height:20px;color:#8a97a6;">此邮件由 {$e($d['site'])} 询盘表单自动发送 · 副本见网站后台 Flamingo</td></tr></table>
</td></tr></table>
HTML;
};
