// import { xxx } from "node:path"; This is ESM syntax. 
// `require()` is CJS syntax (filename.cjs). 
/*
早上我在这研究这个东西，一开始是如何获取request返回码，找到在vite的一个js文件里，但不知道怎么导出来
问ai，告诉我一个CJS代码，一个ESM代码，搞了半天不知道怎么运行。
我妈就给我来送蛋白糊，一边搅合一边放在我桌子上，她搅合的动作非常快告我的很心烦。
我还一边担心她搅合的不匀，一边心烦这个AI告诉我的什么玩意！
我在电脑上进行不同的活动似乎都有……呼应。好像在指责我在这瞎忙活什么玩意！没人需要……我这是在玩还是在干什么。
说不是玩吧，除了研究这些东西，实在是没什么好玩的。
说是玩吧，这也不是玩具，也不是游戏，我都TM的混不上饭吃当然不是在玩了！
一点希望没有吗？一点希望不给吗？我只能去送外卖吗？他妈的我感觉送外卖的都不能要我！他要我也没什么便宜好得的！
早晚都的还！

希望这些话别把送外卖的勾来烦我！都塔曼给我滚远点！谢谢
*/
console.log("这行代码会执行");
// 下面的代码不会执行
/*
console.log("这行代码不会执行");
*/


import { fileURLToPath } from "node:url";
import { dirname } from "node:path";

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);


import { resolve } from "node:path";
import { pathToFileURL } from "node:url";
import { createRequire } from "node:module";

// 1) Read CommonJS module.exports with require()
const require = createRequire(import.meta.url);
let cjsData;
try {
	cjsData = require("./ttools_sample-cjs.cjs");
} catch {
	cjsData = null;
}
console.log("[CJS] module.exports:");
console.log(cjsData);

// 2) Read ESM export with dynamic import()
async function readEsmExports() {
	const esmPath = resolve(__dirname, "fe-p5/node_modules/vite/dist/node/chunks/node.js");
	const esmModule = await import(pathToFileURL(esmPath).href);

	const keys = Object.keys(esmModule);
	console.log("\n[ESM] export keys (first 20):");
	console.log(keys.slice(0, 20));
	console.log(`[ESM] total exports: ${keys.length}`);
}

readEsmExports().catch((err) => {
	console.error("Failed to import ESM module:", err);
	process.exitCode = 1;
});