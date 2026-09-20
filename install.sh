#!/usr/bin/env bash
# Koilink 一键安装/升级脚本（在 Zeabur WordPress 服务的终端里执行）：
#   curl -sL https://raw.githubusercontent.com/sue1231511/koilink/main/install.sh | bash

cd /tmp
rm -rf koilink-main k.zip

echo "==> 下载最新代码..."
curl -sL https://github.com/sue1231511/koilink/archive/refs/heads/main.zip -o k.zip

echo "==> 解压..."
php -r '$z=new ZipArchive();if($z->open("k.zip")!==true){fwrite(STDERR,"解压失败\n");exit(1);}$z->extractTo(".");$z->close();echo "OK\n";'

echo "==> 安装主题 koilink-theme..."
rm -rf /var/www/html/wp-content/themes/koilink-theme
cp -r koilink-main/koilink-theme /var/www/html/wp-content/themes/

echo "==> 同步插件 koilink-core..."
rm -rf /var/www/html/wp-content/plugins/koilink-core
cp -r koilink-main/koilink-core /var/www/html/wp-content/plugins/

echo ""
echo "=== 完成！首次安装需去后台：外观 → 主题 → 启用 Koilink ==="
