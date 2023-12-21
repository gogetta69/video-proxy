# Easy Video Proxy & HLS Proxy

Easy Video Proxy &amp; HLS Proxy repository, a streamlined solution for proxying video content and HTTP Live Streaming (HLS). This repository contains two main components: a straightforward video proxy and an HLS proxy, each designed to cater to different streaming needs and scenarios.

# Video Streaming Proxies

This repository contains two PHP scripts designed for video streaming: `video_proxy.php` for direct video streaming and `hls_proxy.php` for HTTP Live Streaming (HLS).

## video_proxy.php

### Description
`video_proxy.php` is a script for proxying various video formats like MP4, MKV, AVI, etc. 

### How to Use
1. **Deploy** the script to your web server.
2. **Stream a Video** by making a request to the script with the video URL as a parameter.  
   Example: `http://yourserver.com/video_proxy.php?url=http://example.com/video.mp4`
3. **Parameters**: The script accepts additional parameters for customization, like setting headers or handling different formats.

## hls_proxy.php

### Description
`hls_proxy.php` is specifically designed for proxying HLS (M3U8) streams. It handles master playlists, media playlists, and key requests, suitable for HLS content delivery through a proxy.

### How to Use
1. **Upload** `hls_proxy.php` to your web server.
2. **Proxy HLS Content** by directing HLS requests to the script. It fetches and relays HLS content (m3u8 files, segments) to the client.
3. **Example Request**: For a master playlist at `http://example.com/stream.m3u8`, access it via the proxy: `http://yourserver.com/hls_proxy.php?url=http://example.com/stream.m3u8`

## Passing Custom Headers Using the `&data=` Parameter

Both `video_proxy.php` and `hls_proxy.php` scripts support the use of the `&data=` parameter to pass additional HTTP headers, such as `User-Agent` and `Referer`, to the proxy request. This functionality can be crucial for accessing servers that require specific headers for proper response.

### Format

Headers should be formatted as a pipe-separated (`|`) list, with each header-value pair enclosed in double quotes and connected by an equals sign (`=`). For example:

User-Agent="Your User Agent"|Referer="https://example.com/"

### Base64 Encoding

The header string must be Base64 encoded. This step is important to ensure that the string is correctly transmitted in the URL without issues caused by URL encoding or special characters.

### Example

Suppose you want to pass these headers:
- `User-Agent`: `Mozilla/5.0 (Windows NT 10.0; rv:109.0) Gecko/20100101 Firefox/115.0`
- `Referer`: `https://example.com/`

First, format your headers:

User-Agent="Mozilla/5.0 (Windows NT 10.0; rv:109.0) Gecko/20100101 Firefox/115.0"|Referer="https://example.com/"

Then, Base64 encode the string. The example becomes something like:

VXNlci1BZ2VudD0iTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgcnY6MTA5LjApIEdlY2tvLzIwMTAwMTAxIEZpcmVmb3gvMTE1LjAiUmVmZXJlcj0iaHR0cHM6Ly9leGFtcGxlLmNvbS8i

### Using in Proxy Request

Append the encoded string to the URL as the `&data=` parameter value:

http://yourserver.com/video_proxy.php?url=http://example.com/video.mp4&data=VXNlci1BZ2VudD0iTW96aWxsYS81LjAgKFdpbmRvd3MgTlQgMTAuMDsgcnY6MTA5LjApIEdlY2tvLzIwMTAwMTAxIEZpcmVmb3gvMTE1LjAiUmVmZXJlcj0iaHR0cHM6Ly9leGFtcGxlLmNvbS8i

This request will pass along the specified `User-Agent` and `Referer` headers with the video request.


