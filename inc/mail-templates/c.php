<?php
/** C 设备铭牌: a white header with the logo, a dark plate for the product asked about, then the sender, message and reply. */
return static function (array $d): string {
    $f = "font-family:-apple-system,BlinkMacSystemFont,'PingFang SC','Hiragino Sans GB','Microsoft YaHei','Helvetica Neue',Arial,sans-serif;";
    $mono = "font-family:Menlo,Consolas,'SFMono-Regular','Courier New',monospace;";
    $e = 'esc_html';
    $empty = static fn(string $text): string => '<span style="color:#9aa6b4;">' . $text . '</span>';
    $chip = '<span style="display:inline-block;padding:3px 10px;border-radius:999px;background:#f5b301;color:#0b1f33;' . $f . 'font-size:13px;font-weight:600;line-height:20px;">' . $e($d['type']) . '</span>';
    if ($d['product']) {
        $p = $d['product'];
        $photo = $p['image'] ? '<td width="104" valign="top" style="padding:18px 0 18px 18px;"><img src="' . esc_url($p['image']) . '" width="104" height="104" alt="' . esc_attr($p['name']) . '" style="display:block;width:104px;height:104px;border-radius:8px;background:#ffffff;"></td>' : '';
        $plate = $photo . '<td valign="middle" style="padding:18px 20px;' . $f . '"><div style="font-size:12px;letter-spacing:2px;color:#7f93a8;">询问的产品</div>'
            . '<div style="padding-top:6px;font-size:19px;font-weight:700;line-height:27px;color:#ffffff;white-space:nowrap;">' . $e($p['title']) . '</div>'
            . ($p['full'] !== ''
                ? '<div style="padding-top:4px;font-size:13px;line-height:19px;color:#c5d2df;">' . $e($p['full']) . '</div>'
                : '<div style="padding-top:8px;' . $mono . 'font-size:14px;line-height:20px;color:#c5d2df;white-space:nowrap;">MODEL&nbsp;' . $e($d['model']) . '</div>')
            . '<div style="padding-top:10px;">' . $chip . '</div></td>';
    } else {
        $model = $d['model'] !== '' ? '<div style="padding-top:8px;' . $mono . 'font-size:14px;line-height:20px;color:#c5d2df;white-space:nowrap;">MODEL&nbsp;' . $e($d['model']) . '</div>' : '';
        $plate = '<td style="padding:18px 20px;' . $f . '"><div style="font-size:12px;letter-spacing:2px;color:#7f93a8;">询盘类型</div>' . $model . '<div style="padding-top:10px;">' . $chip . '</div></td>';
    }
    $row = static fn(string $label, string $value): string => '<tr><td width="96" valign="top" style="padding:9px 0;' . $f . 'font-size:14px;color:#6b7a8c;">' . $label . '</td><td valign="top" style="padding:9px 0;' . $f . 'font-size:16px;line-height:25px;color:#0b1f33;word-break:break-word;">' . $value . '</td></tr>';
    $rows = $row('姓名', '<b>' . $e($d['name']) . '</b>')
        . $row('邮箱', '<a href="mailto:' . esc_attr($d['email']) . '" style="color:#215bdb;text-decoration:none;">' . $e($d['email']) . '</a>')
        . $row('公司', $d['company'] !== '' ? $e($d['company']) : $empty('未填写'));
    $message = $d['message'] !== '' ? bl_wp_inquiry_text($d['message']) : $empty('客户未留言');
    // Country, IP and time as label rows: on a phone that fits the mail to its screen, one line of all three broke mid-word.
    $fact = static fn(string $label, string $value, string $font): string => '<tr><td width="96" valign="top" style="padding:5px 0;' . $f . 'font-size:14px;line-height:22px;color:#6b7a8c;">' . $label . '</td><td valign="top" style="padding:5px 0;' . $font . 'font-size:15px;line-height:22px;color:#0b1f33;">' . $value . '</td></tr>';
    $trace = $fact('来源国家', $e($d['country'] !== '' ? $d['country'] : '未知'), $f)
        . ($d['ip'] !== '' ? $fact('IP 地址', $e($d['ip']), $mono) : '')
        . $fact('提交时间', $e($d['time']), $mono);
    $logo = esc_url($d['logo']);
    $reply = esc_url($d['reply'], ['mailto']);
    $url = $d['url'] !== '' ? '&nbsp;&nbsp;<a href="' . esc_url($d['url']) . '" style="color:#9fb3c8;text-decoration:none;word-break:break-all;">' . $e($d['url']) . '</a>' : '';

    return <<<HTML
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:#dfe4ea;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;text-size-adjust:100%;">
<tr><td align="center" style="padding:32px 12px;">
<table role="presentation" width="660" cellpadding="0" cellspacing="0" border="0" style="width:660px;background:#ffffff;border-radius:14px;border-collapse:separate;text-align:left;">
<tr><td bgcolor="#ffffff" style="background:#ffffff;padding:18px 36px;border-radius:14px 14px 0 0;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td valign="middle"><img src="{$logo}" width="112" height="45" alt="BOB-LIFT" style="display:block;width:112px;height:45px;border:0;"></td>
<td align="right" valign="middle" style="{$mono}font-size:13px;color:#6b7a8c;white-space:nowrap;">{$e($d['time'])}</td></tr></table></td></tr>
<tr><td bgcolor="#0b1f33" style="background:#0b1f33;padding:30px 36px 32px;">
<div style="{$f}font-size:28px;font-weight:800;line-height:36px;color:#ffffff;">新询盘通知</div>
<div style="padding:8px 0 22px;{$f}font-size:16px;line-height:26px;color:#b4c2d0;">你好，管理员：有客户在网站上询问了下面这台设备。</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #23394f;border-radius:12px;border-collapse:separate;"><tr>{$plate}</tr></table>
<div style="padding-top:14px;{$f}font-size:13px;line-height:20px;color:#7f93a8;"><span style="color:#f5b301;">●</span>&nbsp;{$e($d['kind'])}{$url}</div>
</td></tr>
<tr><td style="padding:30px 36px 6px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">{$rows}</table></td></tr>
<tr><td style="padding:12px 36px 0;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td style="border:1px solid #e3e8ef;border-radius:10px;padding:18px 20px;{$f}">
<div style="padding-bottom:8px;font-size:13px;font-weight:700;color:#6b7a8c;">留言</div>
<div style="font-size:16px;line-height:26px;color:#0b1f33;word-break:break-word;">{$message}</div></td></tr></table></td></tr>
<tr><td style="padding:22px 36px 0;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f5f7fa" style="background:#f5f7fa;border-radius:10px;border-collapse:separate;"><tr>
<td style="padding:11px 20px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">{$trace}</table></td></tr></table></td></tr>
<tr><td style="padding:28px 36px 0;"><table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" bgcolor="#215bdb" style="background:#215bdb;border-radius:8px;">
<a href="{$reply}" style="display:block;padding:15px 32px;{$f}font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:8px;white-space:nowrap;">立即回复客户 &rarr;</a>
</td></tr></table>
<div style="padding-top:10px;{$f}font-size:14px;line-height:22px;color:#6b7a8c;">也可以直接回复这封邮件</div></td></tr>
<tr><td style="padding:30px 36px 28px;"><div style="border-top:1px solid #eef1f5;padding-top:18px;{$f}font-size:12px;line-height:20px;color:#8a97a6;">此邮件由 {$e($d['site'])} 询盘表单自动发送 · 副本见网站后台 Flamingo</div></td></tr>
</table>
</td></tr></table>
HTML;
};
