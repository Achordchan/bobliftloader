<?php
/** D 来信: warm paper, the sender's message set as a letter in the middle, everything else under 「附：询盘详情」. */
return static function (array $d): string {
    $f = "font-family:-apple-system,BlinkMacSystemFont,'PingFang SC','Hiragino Sans GB','Microsoft YaHei','Helvetica Neue',Arial,sans-serif;";
    $mono = "font-family:Menlo,Consolas,'SFMono-Regular','Courier New',monospace;";
    $serif = "font-family:Georgia,'Songti SC','STSong','SimSun',serif;";
    $e = 'esc_html';
    $chip = static fn(string $text, string $bg, string $color): string => '<span style="display:inline-block;padding:3px 10px;border-radius:999px;background:' . $bg . ';color:' . $color . ';' . $f . 'font-size:13px;font-weight:600;line-height:20px;">' . esc_html($text) . '</span>';
    $row = static fn(string $label, string $value): string => '<tr><td width="104" valign="top" style="padding:10px 0;border-top:1px solid #e8e3da;' . $f . 'font-size:13px;color:#6f6a62;">' . $label . '</td><td valign="top" style="padding:10px 0;border-top:1px solid #e8e3da;' . $f . 'font-size:15px;line-height:23px;color:#0b1f33;word-break:break-word;">' . $value . '</td></tr>';
    $who = '<b>' . $e($d['name']) . '</b>' . ($d['company'] !== '' ? '&nbsp;·&nbsp;' . $e($d['company']) : '');
    $letter = $d['message'] !== ''
        ? '<div style="' . $serif . 'font-size:16px;line-height:28px;color:#1f2a36;word-break:break-word;">' . bl_wp_inquiry_text($d['message']) . '</div>'
        : '<div style="' . $serif . 'font-size:16px;line-height:28px;color:#a39d93;font-style:italic;">客户没有填写留言，只留下了联系方式。</div>';
    if ($d['product']) {
        $p = $d['product'];
        $photo = $p['image'] ? '<td width="104" valign="top" style="padding-right:18px;"><img src="' . esc_url($p['image']) . '" width="104" height="104" alt="' . esc_attr($p['name']) . '" style="display:block;width:104px;height:104px;border-radius:4px;"></td>' : '';
        $details = '<tr><td colspan="2" style="padding:14px 0;border-top:1px solid #e8e3da;"><table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>' . $photo
            . '<td valign="middle" style="' . $f . '"><div style="font-size:16px;font-weight:700;line-height:24px;color:#0b1f33;">' . $e($p['name']) . '</div>'
            . '<div style="padding-top:4px;font-size:14px;color:#6f6a62;">型号 ' . $e($d['model']) . '</div>'
            . '<div style="padding-top:8px;">' . $chip($d['type'], '#e8effd', '#215bdb') . '</div></td></tr></table></td></tr>';
    } else {
        $details = ($d['model'] !== '' ? $row('型号', $e($d['model'])) : '') . $row('询盘类型', $chip($d['type'], '#e8effd', '#215bdb'));
    }
    $details .= $row('来源页面', ($d['url'] !== '' ? '<a href="' . esc_url($d['url']) . '" style="color:#215bdb;text-decoration:none;word-break:break-all;">' . $e($d['url']) . '</a>&nbsp; ' : '') . $chip($d['kind'], '#f1ede6', '#6f6a62'))
        . $row('来源国家', $e($d['country'] !== '' ? $d['country'] : '未知'))
        . ($d['ip'] !== '' ? $row('IP 地址', '<span style="' . $mono . '">' . $e($d['ip']) . '</span>') : '')
        . $row('提交时间', $e($d['time']));
    $logo = esc_url($d['logo']);
    $reply = esc_url($d['reply'], ['mailto']);
    $mailto = esc_url('mailto:' . $d['email'], ['mailto']);

    return <<<HTML
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:#f3f0ea;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;text-size-adjust:100%;">
<tr><td align="center" style="padding:32px 12px;">
<table role="presentation" width="660" cellpadding="0" cellspacing="0" border="0" style="width:660px;"><tr><td style="padding:0 8px 18px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td valign="middle"><img src="{$logo}" width="112" height="45" alt="BOB-LIFT" style="display:block;width:112px;height:45px;border:0;"></td>
<td align="right" style="{$f}font-size:13px;color:#6f6a62;white-space:nowrap;">提交于 {$e($d['time'])}</td></tr></table></td></tr></table>
<table role="presentation" width="660" cellpadding="0" cellspacing="0" border="0" style="width:660px;background:#ffffff;border:1px solid #e8e3da;border-radius:4px;border-collapse:separate;text-align:left;">
<tr><td style="padding:34px 44px 0;{$f}">
<div style="font-size:24px;font-weight:800;line-height:32px;color:#0b1f33;">新询盘通知</div>
<div style="padding-top:6px;font-size:16px;line-height:26px;color:#6f6a62;">你好，管理员。以下是客户写来的原话：</div>
</td></tr>
<tr><td style="padding:26px 44px 0;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td bgcolor="#fbf9f5" style="background:#fbf9f5;border:1px solid #e8e3da;border-radius:4px;padding:26px 30px;">
<div style="{$f}font-size:16px;line-height:26px;color:#0b1f33;word-break:break-word;">{$who}</div>
<div style="{$f}font-size:16px;line-height:26px;"><a href="{$mailto}" style="color:#215bdb;text-decoration:none;">{$e($d['email'])}</a></div>
<div style="height:1px;line-height:1px;font-size:0;margin:18px 0;border-top:1px dashed #d8d1c4;">&nbsp;</div>
{$letter}
</td></tr></table></td></tr>
<tr><td style="padding:26px 44px 0;"><table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" bgcolor="#215bdb" style="background:#215bdb;border-radius:4px;">
<a href="{$reply}" style="display:block;padding:15px 32px;{$f}font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:4px;white-space:nowrap;">立即回复客户 &rarr;</a></td></tr></table>
<div style="padding-top:10px;{$f}font-size:14px;line-height:22px;color:#6f6a62;">也可以直接回复这封邮件</div></td></tr>
<tr><td style="padding:38px 44px 0;{$f}font-size:12px;font-weight:700;letter-spacing:2px;color:#6f6a62;">附：询盘详情</td></tr>
<tr><td style="padding:10px 44px 34px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">{$details}</table></td></tr>
</table>
<table role="presentation" width="660" cellpadding="0" cellspacing="0" border="0" style="width:660px;"><tr>
<td align="center" style="padding:20px 8px 0;{$f}font-size:12px;line-height:20px;color:#9a948a;">此邮件由 {$e($d['site'])} 询盘表单自动发送 · 副本见网站后台 Flamingo</td></tr></table>
</td></tr></table>
HTML;
};
