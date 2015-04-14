(function(a){a.fn.splitter=function(b){b=b||{};return this.each(function(){var n;function i(s){if(c.outline){n=n||m.clone(false).insertAfter(g)}o.css("-webkit-user-select","none");m.addClass(c.activeClass);g._posSplit=g[0][c.pxSplit]-s[c.eventPos];a(document).bind("mousemove",k).bind("mouseup",q)}function k(s){var t=g._posSplit+s[c.eventPos];if(c.outline){t=Math.max(0,Math.min(t,d._DA-m._DA));m.css(c.origin,t)}else{l(t)}}function q(s){m.removeClass(c.activeClass);var t=g._posSplit+s[c.eventPos];if(c.outline){n.remove();n=null;l(t)}o.css("-webkit-user-select","text");a(document).unbind("mousemove",k).unbind("mouseup",q)}function l(s){s=Math.max(g._min,d._DA-f._max,Math.min(s,g._max,d._DA-m._DA-f._min));m._DA=m[0][c.pxSplit];m.css(c.origin,s).css(c.fixed,d._DF);g.css(c.origin,0).css(c.split,s).css(c.fixed,d._DF);f.css(c.origin,s+m._DA).css(c.split,d._DA-m._DA-s).css(c.fixed,d._DF);if(!a.browser.msie){o.trigger("resize")}}function p(v,u){var t=0;for(var s=1;s<arguments.length;s++){t+=Math.max(parseInt(v.css(arguments[s]))||0,0)}return t}var j=(b.splitHorizontal?"h":b.splitVertical?"v":b.type)||"v";var c=a.extend({activeClass:"active",pxPerKey:8,tabIndex:0,accessKey:""},{v:{keyLeft:39,keyRight:37,cursor:"e-resize",splitbarClass:"vsplitbar",outlineClass:"voutline",type:"v",eventPos:"pageX",origin:"left",split:"width",pxSplit:"offsetWidth",side1:"Left",side2:"Right",fixed:"height",pxFixed:"offsetHeight",side3:"Top",side4:"Bottom"},h:{keyTop:40,keyBottom:38,cursor:"n-resize",splitbarClass:"hsplitbar",outlineClass:"houtline",type:"h",eventPos:"pageY",origin:"top",split:"height",pxSplit:"offsetHeight",side1:"Top",side2:"Bottom",fixed:"width",pxFixed:"offsetWidth",side3:"Left",side4:"Right"}}[j],b);var d=a(this).css({position:"relative"});var o=a(">*",d[0]).css({position:"absolute","z-index":"1","-moz-outline-style":"none"});var g=a(o[0]);var f=a(o[1]);var e=a('<a href="javascript:void(0)"></a>').attr({accessKey:c.accessKey,tabIndex:c.tabIndex,title:c.splitbarClass}).bind(a.browser.opera?"click":"focus",function(){this.focus();m.addClass(c.activeClass)}).bind("keydown",function(u){var t=u.which||u.keyCode;var s=t==c["key"+c.side1]?1:t==c["key"+c.side2]?-1:0;if(s){l(g[0][c.pxSplit]+s*c.pxPerKey,false)}}).bind("blur",function(){m.removeClass(c.activeClass)});var m=a(o[2]||"<div></div>").insertAfter(g).css("z-index","100").append(e).attr({"class":c.splitbarClass,unselectable:"on"}).css({position:"absolute","user-select":"none","-webkit-user-select":"none","-khtml-user-select":"none","-moz-user-select":"none"}).bind("mousedown",i);if(/^(auto|default|)$/.test(m.css("cursor"))){m.css("cursor",c.cursor)}m._DA=m[0][c.pxSplit];d._PBF=a.boxModel?p(d,"border"+c.side3+"Width","border"+c.side4+"Width"):0;d._PBA=a.boxModel?p(d,"border"+c.side1+"Width","border"+c.side2+"Width"):0;g._pane=c.side1;f._pane=c.side2;a.each([g,f],function(){this._min=c["min"+this._pane]||p(this,"min-"+c.split);this._max=c["max"+this._pane]||p(this,"max-"+c.split)||9999;this._init=c["size"+this._pane]===true?parseInt(a.curCSS(this[0],c.split)):c["size"+this._pane]});var r=g._init;if(!isNaN(f._init)){r=d[0][c.pxSplit]-d._PBA-f._init-m._DA}if(c.cookie){if(!a.cookie){alert("jQuery.splitter(): jQuery cookie plugin required")}var h=parseInt(a.cookie(c.cookie));if(!isNaN(h)){r=h}a(window).bind("unload",function(){var s=String(m.css(c.origin));a.cookie(c.cookie,s,{expires:c.cookieExpires||365,path:c.cookiePath||document.location.pathname})})}if(isNaN(r)){r=Math.round((d[0][c.pxSplit]-d._PBA-m._DA)/2)}if(c.anchorToWindow){d._hadjust=p(d,"borderTopWidth","borderBottomWidth","marginBottom");d._hmin=Math.max(p(d,"minHeight"),20);a(window).bind("resize",function(){var t=d.offset().top;var s=a(window).height();d.css("height",Math.max(s-t-d._hadjust,d._hmin)+"px");if(!a.browser.msie){d.trigger("resize")}}).trigger("resize")}else{if(c.resizeToWidth&&!a.browser.msie){a(window).bind("resize",function(){d.trigger("resize")})}}d.bind("resize",function(t,s){if(t.target!=this){return}d._DF=d[0][c.pxFixed]-d._PBF;d._DA=d[0][c.pxSplit]-d._PBA;if(d._DF<=0||d._DA<=0){return}l(!isNaN(s)?s:(!(c.sizeRight||c.sizeBottom)?g[0][c.pxSplit]:d._DA-f[0][c.pxSplit]-m._DA))}).trigger("resize",[r])})}})(jQuery);