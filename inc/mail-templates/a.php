<?php
/** A 出厂工单: a white header with a blue rule, then numbered sections (source, product, sender, trace) like a work order. */
return static function (array $d): string {
    $f = "font-family:-apple-system,BlinkMacSystemFont,'PingFang SC','Hiragino Sans GB','Microsoft YaHei','Helvetica Neue',Arial,sans-serif;";
    $mono = "font-family:Menlo,Consolas,'SFMono-Regular','Courier New',monospace;";
    $e = 'esc_html';
    $empty = static fn(string $text): string => '<span style="color:#9aa6b4;">' . $text . '</span>';
    $chip = static fn(string $text, string $bg, string $color): string => '<span style="display:inline-block;padding:3px 10px;border-radius:999px;background:' . $bg . ';color:' . $color . ';' . $f . 'font-size:13px;font-weight:600;line-height:20px;">' . esc_html($text) . '</span>';
    $section = static fn(string $num, string $title): string => '<tr><td colspan="2" style="padding:26px 0 10px;' . $f . 'font-size:13px;font-weight:700;letter-spacing:1px;color:#215bdb;border-bottom:1px solid #e3e8ef;"><span style="' . $mono . '">' . $num . '</span>&nbsp;&nbsp;' . $title . '</td></tr>';
    $row = static fn(string $label, string $value, string $font = ''): string => '<tr><td width="88" valign="top" style="padding:12px 0;border-bottom:1px solid #eef1f5;' . $f . 'font-size:14px;color:#6b7a8c;">' . $label . '</td><td valign="top" style="padding:12px 0;border-bottom:1px solid #eef1f5;' . ($font ?: $f) . 'font-size:16px;line-height:24px;color:#0b1f33;word-break:break-word;">' . $value . '</td></tr>';

    $rows = $section('01', '来源页面');
    if ($d['url'] !== '') $rows .= $row('页面', '<a href="' . esc_url($d['url']) . '" style="color:#215bdb;text-decoration:none;word-break:break-all;">' . $e($d['url']) . '</a>');
    $rows .= $row('类型', $chip($d['kind'], '#eef1f5', '#4a5a6c'));
    $rows .= $section('02', '产品信息');
    if ($d['product']) {
        $p = $d['product'];
        $photo = $p['image'] ? '<td width="104" valign="top" style="padding-right:18px;"><img src="' . esc_url($p['image']) . '" width="104" height="104" alt="' . esc_attr($p['name']) . '" style="display:block;width:104px;height:104px;border-radius:6px;border:1px solid #e3e8ef;"></td>' : '';
        $rows .= '<tr><td colspan="2" style="padding:16px 0;border-bottom:1px solid #eef1f5;"><table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>' . $photo
            . '<td valign="middle" style="' . $f . '"><div style="font-size:18px;font-weight:700;line-height:26px;color:#0b1f33;' . ($p['full'] !== '' ? 'white-space:nowrap;' : '') . '">' . $e($p['title']) . '</div>'
            . ($p['full'] !== ''
                ? '<div style="padding-top:4px;font-size:13px;line-height:19px;color:#6b7a8c;">' . $e($p['full']) . '</div>'
                : '<div style="padding-top:4px;' . $mono . 'font-size:14px;color:#6b7a8c;">型号 ' . $e($d['model']) . '</div>')
            . '<div style="padding-top:10px;">' . $chip($d['type'], '#e8effd', '#215bdb') . '</div></td></tr></table></td></tr>';
    } else {
        if ($d['model'] !== '') $rows .= $row('型号', $e($d['model']), $mono);
        $rows .= $row('询盘类型', $chip($d['type'], '#e8effd', '#215bdb'));
    }
    $rows .= $section('03', '客户信息')
        . $row('姓名', $e($d['name']))
        . $row('邮箱', '<a href="mailto:' . esc_attr($d['email']) . '" style="color:#215bdb;text-decoration:none;">' . $e($d['email']) . '</a>')
        . $row('公司', $d['company'] !== '' ? $e($d['company']) : $empty('未填写'))
        . $row('留言', $d['message'] !== '' ? bl_wp_inquiry_text($d['message']) : $empty('客户未留言'));
    $rows .= $section('04', '来源追踪')
        . $row('国家', $e($d['country'] !== '' ? $d['country'] : '未知'))
        . ($d['ip'] !== '' ? $row('IP 地址', $e($d['ip']), $mono) : '')
        . $row('提交时间', $e($d['time']), $mono);
    $logo = esc_url($d['logo']);
    $reply = esc_url($d['reply'], ['mailto']);

    return <<<HTML
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:#eef1f5;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;text-size-adjust:100%;">
<tr><td align="center" style="padding:32px 12px;">
<table role="presentation" width="660" cellpadding="0" cellspacing="0" border="0" style="width:660px;background:#ffffff;border:1px solid #dbe2ea;border-radius:10px;border-collapse:separate;overflow:hidden;text-align:left;">
<tr><td bgcolor="#ffffff" style="background:#ffffff;padding:18px 36px;border-bottom:4px solid #215bdb;border-radius:10px 10px 0 0;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td valign="middle"><img src="{$logo}" width="112" height="45" alt="BOB-LIFT" style="display:block;width:112px;height:45px;border:0;"></td>
<td align="right" valign="middle" style="{$f}font-size:13px;color:#6b7a8c;white-space:nowrap;">提交于 <span style="{$mono}color:#0b1f33;">{$e($d['time'])}</span></td></tr></table></td></tr>
<tr><td style="padding:32px 36px 8px;{$f}">
<div style="font-size:28px;font-weight:800;line-height:36px;color:#0b1f33;">新询盘通知</div>
<div style="padding-top:8px;font-size:16px;line-height:26px;color:#4a5a6c;">你好，管理员：网站刚收到一条新询盘，以下是客户提交的全部信息。</div>
</td></tr>
<tr><td style="padding:0 36px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">{$rows}</table></td></tr>
<tr><td style="padding:32px 36px 8px;"><table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"><tr>
<td align="center" bgcolor="#215bdb" style="background:#215bdb;border-radius:8px;"><a href="{$reply}" style="display:block;padding:15px 32px;{$f}font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:8px;">立即回复客户 &rarr;</a></td>
</tr></table></td></tr>
<tr><td align="center" style="padding:4px 36px 32px;{$f}font-size:14px;color:#6b7a8c;">也可以直接回复这封邮件</td></tr>
<tr><td bgcolor="#f6f8fb" style="background:#f6f8fb;padding:18px 36px;border-top:1px solid #e3e8ef;border-radius:0 0 10px 10px;{$f}font-size:12px;line-height:20px;color:#8a97a6;text-align:center;">此邮件由 {$e($d['site'])} 询盘表单自动发送 · 副本见网站后台 Flamingo</td></tr>
</table>
</td></tr></table>
HTML;
};
