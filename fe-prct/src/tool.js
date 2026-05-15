import { createRequire } from "node:module";
const require = createRequire(import.meta.url);
const codes = require('statuses/codes.json');
var status = require('statuses')
console.log(codes);
console.log(codes["404"]);