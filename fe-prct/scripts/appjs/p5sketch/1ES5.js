// asciimage


const sz = [453,240]
const ascii_rate = 1/9
const src_rate = 1.25

const fontsz = 21

const cvsSz = [sz[0]*src_rate,sz[1]*src_rate]

const fsz0wh1 = 0

var myAsciiArt;
var asciiart_width = Math.floor(sz[0]*ascii_rate); var asciiart_height = Math.floor(sz[1]*ascii_rate);

var images = [];

function preload() {
    dkfont = loadFont('./docs/assets/fonts/SourceCodePro-Black.otf');
    images[0] = loadImage('./docs/assets/images/hagongcheng.png');
}

function setup() {

	createCanvas(cvsSz[0],cvsSz[1])
    background(255)
    fill(0)
    
    textFont(dkfont)
    textSize(fontsz)

    char2ascii(0)
    char2ascii(1)

}


function char2ascii(fw01){

    // 33 - 126
    let ii,jj,xx,yy
    for (let idx=33;idx<127;idx++){

        if (fw01==0){
            ii = (idx-33) % Math.floor(cvsSz[0]/fontsz)
            jj = Math.floor((idx-33)/(cvsSz[0]/fontsz))+1;

            
            [xx,yy] = [ii*fontsz,jj*fontsz]
            text(String.fromCodePoint(idx),xx,yy)
        }
        else if (fw01==1){
            ii= (idx-33)%asciiart_width
            jj = Math.floor((idx-33)/asciiart_width)+1;


            [xx,yy] = [cvsSz[0]*ii/asciiart_width,cvsSz[1]*jj/asciiart_height+222]
            text(String.fromCodePoint(idx),xx,yy)
        }

    }

}