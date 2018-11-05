# Brightcove Learning Services: Sample Proxy Apps

The Brightcove Platform APIs are generally not CORS-enabled, meaning that you cannot access them directly from a web app like the ones that we in Brightcove Learning services create as examples for you to learn from: [Platform API Code Samples](https://support.brightcove.com/platform-api-code-samples).

To get around this, all you need to do is route the API requests through a server-side app often called a proxy. The proxy takes the request and other information sent from a web page via JavaScript, gets an access token, makes the API request, and then returns the response to the calling page.

![Proxy Archtecture Diagram](./images/ProxyArchitecture.svg)
