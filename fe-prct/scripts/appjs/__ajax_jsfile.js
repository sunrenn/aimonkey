
export function ajaxP5JsFile(sId, url) {

    function objXMLHttpRequest(somefun) {
        
        let oXHR;

        if (window.XMLHttpRequest) // Gecko  
            oXHR = new XMLHttpRequest();
        else if (window.ActiveXObject) // IE  
            oXHR = new ActiveXObject("MsXml2.XmlHttp");

        oXHR.onreadystatechange = function () {
            if (oXHR.readyState == 4 && oXHR.status === 200) {
                somefun(sId, url, oXHR.responseText);
            }
        }
        oXHR.open('GET', url, false); //这里要使用异步操作，设计回调程序。
        oXHR.send(null);
    }

    objXMLHttpRequest(rebuildJS);

    function rebuildJS(sId, fileUrl, source, mode="rebuild") {
        if (source != null) {
            var oScript = document.getElementById(sId);
            if (oScript) {
                if (mode=="append"){
                    oScript.text += source;
                    return
                }
                else if (mode=="rebuild"){
                    oScript.remove();
                }
                else{
                    console.warn("unknown mode for rebuildJS",mode)
                    return
                }
            }
            var oHead = document.getElementsByTagName('HEAD').item(0);
            var oScript = document.createElement("script");
            // oScript.type = "text/javascript";
            oScript.type = "module";
            oScript.id = sId;
            oScript.text = source;
            oHead.appendChild(oScript);
        }
    }

}
