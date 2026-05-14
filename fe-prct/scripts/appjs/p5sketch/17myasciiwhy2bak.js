// asciimage


const code_is_black0_or_white1 = 0

const base_size = [640,533]
const canvase_size_scale_rate = 2

const base_font_size = 12

const canvas_size = [base_size[0]*canvase_size_scale_rate, base_size[1]*canvase_size_scale_rate]

const max_white_value = 223

let g_sampling
let g_asciiart

// arrange_by=="gird_num" 使用, 暂未开发
// const ascii_fontsize_scale_rate = 12
// let int_art_width = Math.floor(base_size[0]*ascii_fontsize_scale_rate);
// let int_art_height = Math.floor(base_size[1]*ascii_fontsize_scale_rate);

let img_org;
let arr_sz_imgorg;

let imgs = [
    {
        "imgurl":"./docs/assets/images/example_image_American_Gothic.jpg",
        "color_bg&char":[255,0]
    }
]

function preload() {
    font_ascii = loadFont("./docs/assets/fonts/SourceCodePro-Regular.otf");

    for (let ii of imgs) {
        ii["img"] = loadImage(ii['imgurl']);
    }
    img_org = imgs[0]["img"]
}

function setup() {

	createCanvas(canvas_size[0],canvas_size[1])

    // image(img_org,0,0,...canvas_size)
    
    g_sampling = createGraphics(base_font_size,base_font_size)
    g_sampling.textFont(font_ascii)
    g_sampling.textSize(base_font_size*1.111111111111111)

    g_asciiart = createGraphics(...canvas_size)
    g_asciiart.textFont(font_ascii)
    g_asciiart.textSize(base_font_size*1.45333)

    arrCharObj1 = get_ascii_arr()

    arr_sz_imgorg = [img_org.width,img_org.height]

    img2ascii(arrCharObj1)

    console.log(arrCharObj1)

}

function img2ascii(arrCharObj){

    img_org.loadPixels()

    let max_col_num = Math.floor(canvas_size[0]/base_font_size)
    let max_row_num = Math.floor(canvas_size[1]/base_font_size)

    // g_asciiart.background(255,255)
    background(imgs[0]["color_bg&char"][0])
    fill(imgs[0]["color_bg&char"][1])
    g_asciiart.fill(imgs[0]["color_bg&char"][1])
    
    for (let mm=0;mm<1+max_row_num;mm++){
        for (let nn=0;nn<1+max_col_num;nn++){

            // let totalColorValue = 0
            // let totalPixNum = 0
            // let flatgray = 0
            // for (let rr=Math.floor(mm*arr_sz_imgorg[1]/max_row_num) ; rr<(mm+1)*arr_sz_imgorg[1]/max_row_num ; rr++){
            //     for (let cc=Math.floor(nn*arr_sz_imgorg[0]*4/max_col_num) ; cc<(nn+1)*arr_sz_imgorg[0]*4/max_col_num ; cc+=4){
            //         totalPixNum++

            //         let iii = cc*4+rr*arr_sz_imgorg[0]*4
            //         if ( img_org.pixels[iii] +  img_org.pixels[iii+1] +  img_org.pixels[iii+2] ){
            //             totalColorValue += img_org.pixels[iii] +  img_org.pixels[iii+1] +  img_org.pixels[iii+2]
            //         }
            //     }
            // }
            // flatgray = totalColorValue/(totalPixNum*3)

            let rr = Math.floor(mm*arr_sz_imgorg[1]/max_row_num)
            let cc = Math.floor(nn*arr_sz_imgorg[0]/max_col_num)
            let ppxx = (rr*arr_sz_imgorg[0]+cc)*4
            
            if (img_org.pixels[ppxx]){
                let flatgray = img_org.pixels[ppxx]
                
                let grayidx = Math.floor((max_white_value-flatgray)*(127-33-1)/max_white_value)
                if (arrCharObj[grayidx]) {
                    g_asciiart.text(arrCharObj[grayidx].char,nn*base_font_size,mm*base_font_size)
                }
                else {
                    console.log("!!!! ERR : ",grayidx)
                }
            }
            
        }
    }
    image(g_asciiart,0,0)
}

function get_ascii_arr(){

    // 33 - 126
    let arrange_by = "fontsize"
    let ii,jj,xx,yy
    let pink = (255,123,3)
    let aCharVal = []
    
    for (let idx=33;idx<127;idx++){

        if (arrange_by=="fontsize"){
            
            ii = (idx-33) % Math.floor(canvas_size[0]/base_font_size +1)
            jj = Math.floor((idx-33) / Math.floor(canvas_size[0]/base_font_size +1));
            
            [xx,yy] = [ii*base_font_size,jj*base_font_size]
            g_sampling.background(255)
            g_sampling.text(String.fromCodePoint(idx),base_font_size*0.2,base_font_size*0.8)

            // Pixels
            g_sampling.loadPixels()
            let d = g_sampling.pixelDensity();
            let fullImage = 4 * (g_sampling.width * d) * (g_sampling.height * d)

            let totalColorValue = 0
            for (let iii = 0; iii < fullImage; iii += 4) {
                totalColorValue += g_sampling.pixels[iii] +  g_sampling.pixels[iii+1] +  g_sampling.pixels[iii+2]
            }
            let flatgray = Math.floor(totalColorValue/(fullImage*0.75))
            aCharVal.push({'char':String.fromCodePoint(idx),'charidx':idx,'light':flatgray})

            g_sampling.updatePixels();
            // Pixels end

            g_sampling.background(flatgray)
            g_sampling.text(String.fromCodePoint(idx),base_font_size*0.2,base_font_size*0.8)
            // image(g_sampling,xx,yy)


        }
        else if (arrange_by=="gird_num"){
            // ii= (idx-33)%int_art_width
            // jj = Math.floor((idx-33)/int_art_width)+1;

            // [xx,yy] = [canvas_size[0]*ii/int_art_width,canvas_size[1]*jj/int_art_height+222]
            // text(String.fromCodePoint(idx),xx,yy)
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