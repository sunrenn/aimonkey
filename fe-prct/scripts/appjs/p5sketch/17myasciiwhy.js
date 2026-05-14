// asciimage


const sz = [640,533]
const ascii_rate = 1/9
const src_rate = 1

const fontsz = 6

const cvsSz = [sz[0]*src_rate,sz[1]*src_rate]

const fsz0wh1 = 0

const whiteVal = 223

let pg
let npg

let myAsciiArt;
let asciiart_width = Math.floor(sz[0]*ascii_rate);
let asciiart_height = Math.floor(sz[1]*ascii_rate);

let orgimage;
let imgsz;

function preload() {
    dkfont = loadFont("./docs/assets/fonts/SourceCodePro-Regular.otf");
    orgimage = loadImage("./docs/assets/images/example_image_American_Gothic.jpg");
}

function setup() {

	createCanvas(cvsSz[0],cvsSz[1])
    background(255)
    fill(0)

    // image(orgimage,0,0,...cvsSz)
    
    pg = createGraphics(fontsz,fontsz)
    pg.textFont(dkfont)
    pg.textSize(fontsz*1.11111)

    npg = createGraphics(...cvsSz)
    npg.textFont(dkfont)
    npg.textSize(fontsz*1.725)

    arrCharObj1 = get_ascii_arr()

    imgsz = [orgimage.width,orgimage.height]

    img2ascii(arrCharObj1)

    console.log(arrCharObj1)

}

function img2ascii(arrCharObj){
    orgimage.loadPixels()
    let max_col_num = Math.floor(cvsSz[0]/fontsz)
    let max_row_num = Math.floor(cvsSz[1]/fontsz)
    npg.background(255,50)
    
    for (let mm=0;mm<1+max_row_num;mm++){
        for (let nn=0;nn<1+max_col_num;nn++){

            // let totalColorValue = 0
            // let totalPixNum = 0
            // let flatgray = 0
            // for (let rr=Math.floor(mm*imgsz[1]/max_row_num) ; rr<(mm+1)*imgsz[1]/max_row_num ; rr++){
            //     for (let cc=Math.floor(nn*imgsz[0]*4/max_col_num) ; cc<(nn+1)*imgsz[0]*4/max_col_num ; cc+=4){
            //         totalPixNum++

            //         let iii = cc*4+rr*imgsz[0]*4
            //         if ( orgimage.pixels[iii] +  orgimage.pixels[iii+1] +  orgimage.pixels[iii+2] ){
            //             totalColorValue += orgimage.pixels[iii] +  orgimage.pixels[iii+1] +  orgimage.pixels[iii+2]
            //         }
            //     }
            // }
            // flatgray = totalColorValue/(totalPixNum*3)

            let rr = Math.floor(mm*imgsz[1]/max_row_num)
            let cc = Math.floor(nn*imgsz[0]/max_col_num)
            let ppxx = (rr*imgsz[0]+cc)*4
            
            if (orgimage.pixels[ppxx]){
                let flatgray = orgimage.pixels[ppxx]
                
                let grayidx = Math.floor((whiteVal-flatgray)*(127-33-1)/whiteVal)
                if (arrCharObj[grayidx]) {
                    npg.text(arrCharObj[grayidx].char,nn*fontsz,mm*fontsz)
                }
                else {
                    console.log("!!!! ERR : ",grayidx)
                }
            }
            
        }
    }
    image(npg,0,0)
}

function get_ascii_arr(){

    // 33 - 126
    let arrange_by = "fontsize"
    let ii,jj,xx,yy
    let pink = (255,123,3)
    let aCharVal = []
    
    for (let idx=33;idx<127;idx++){

        if (arrange_by=="fontsize"){
            
            ii = (idx-33) % Math.floor(cvsSz[0]/fontsz +1)
            jj = Math.floor((idx-33) / Math.floor(cvsSz[0]/fontsz +1));
            
            [xx,yy] = [ii*fontsz,jj*fontsz]
            pg.background(255)
            pg.text(String.fromCodePoint(idx),fontsz*0.2,fontsz*0.8)

            // Pixels
            pg.loadPixels()
            let d = pg.pixelDensity();
            let fullImage = 4 * (pg.width * d) * (pg.height * d)

            let totalColorValue = 0
            for (let iii = 0; iii < fullImage; iii += 4) {
                totalColorValue += pg.pixels[iii] +  pg.pixels[iii+1] +  pg.pixels[iii+2]
            }
            let flatgray = Math.floor(totalColorValue/(fullImage*0.75))
            aCharVal.push({'char':String.fromCodePoint(idx),'charidx':idx,'light':flatgray})

            pg.updatePixels();
            // Pixels end

            pg.background(flatgray)
            pg.text(String.fromCodePoint(idx),fontsz*0.2,fontsz*0.8)
            // image(pg,xx,yy)


        }
        else if (arrange_by=="gird_num"){
            ii= (idx-33)%asciiart_width
            jj = Math.floor((idx-33)/asciiart_width)+1;

            [xx,yy] = [cvsSz[0]*ii/asciiart_width,cvsSz[1]*jj/asciiart_height+222]
            text(String.fromCodePoint(idx),xx,yy)
        }

    }

    aCharVal.sort((a,b)=>{
        if (a.light>b.light) {
            return -1
        }
        return 1
    })

    return aCharVal
}