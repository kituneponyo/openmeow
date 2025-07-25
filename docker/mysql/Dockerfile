# イメージを指定
FROM mysql:8.0

# init.sqlをデータベースの初期化時に実行する
# COPY init.sql /docker-entrypoint-initdb.d/init.sql

# 日本語を使用できるようにする
RUN microdnf update -y \
    && microdnf install -y glibc-locale-source \
    && localedef -i ja_JP -c -f UTF-8 -A /usr/share/locale/locale.alias ja_JP.UTF-8

# 環境変数を設定

ENV LANG ja_JP.UTF-8
ENV LC_ALL ja_JP.UTF-8
