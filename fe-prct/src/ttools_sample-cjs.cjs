const codes = require('statuses/codes.json');
console.log(codes);
console.log(codes['200']); // OK

module.exports = {
  name: "demo-cjs",
  version: "1.0.0",
  features: ["module.exports", "require", "node"],
  codes,
  nested: {
    ok: true
  }
};
