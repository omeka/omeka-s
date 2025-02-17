FROM alpine:latest
WORKDIR /etc/
RUN mkdir -p /etc/Sphinx/build

RUN apk add --no-cache python3 make git py3-pip
RUN pip3 install git+https://github.com/sphinx-doc/sphinx && \
    pip3 uninstall sphinx_rtd_theme && \
    pip3 install sphinx_rtd_theme sphinx-autobuild

CMD sphinx-autobuild -b html --host 0.0.0.0 --port 80 /etc/Sphinx/source /etc/Sphinx/build
