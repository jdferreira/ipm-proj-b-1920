docker run --rm -it \
    -e "HOME=/home/www-data" \
    -v "$PWD:/cwd" -w "/cwd" \
    -v "$HOME/.config/psysh:/home/www-data/.config/psysh" \
    -u $(id -u):$(id -g) \
    -p 8999:8999 \
    php:5.4-cli \
    php -S 0.0.0.0:8999
