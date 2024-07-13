// fetch('produk1.json')
// .then(response=>response.json())
// .then(data=>{
// var data=JSON.parse(data.responseText);
// alert(data.subProduk1[2]);

// // const deskripsi=data.subProduk1[2];
// // const text=document.createTextNode(deskripsi);
// // const p=document.createElement('p');
// // p.appendChild(text);

// })
// .catch(error=>{
// 	alert('error');


//hello

//button back ke halaman depan
// console.log(window.produknya);
window.boolTemplate=false;
const back=document.getElementById('back');
back.onclick=function()
{
window.location.href="index.html";
}


const container=document.getElementsByClassName('container');//container utama pembungkus para div jenis produk



var request=new XMLHttpRequest();

request.open('GET',`produk1.json`,true);
request.onload=function(){

		if(request.status==200)
		{

			var data=JSON.parse(request.responseText);
				 for(var i=0;i<data.nama.nama.length;i++){


					// var p=document.createElement('div');
					// container[0].appendChild(p);
					 var nama=document.createTextNode(data.nama.nama[i]);//nama produk
					 var p=document.createElement('p');//element p untuk nama produk
					 var div=document.createElement('div');//div untuk jenis produk
					p.appendChild(nama);
					var button=document.createElement('button');
				
					var img=document.createElement('img');//gambar jenis produk
					img.src=data.gambar.nama[i];
					div.appendChild(img);
					//div.style.backgroundImage=`url(${data.gambar.nama[i]})`;
					div.appendChild(p);
					
					button.onclick=more;
					// function() {
					// 	var namaProduk=data.nama.nama[i];
					// 	more(namaProduk);
					// };
					div.appendChild(button);
					container[0].appendChild(div);
				





				}



				function more()
				{
					console.log(window.aa);
					if(window.boolTemplate==false){
					var template=document.createElement('div');
					template.classList.add('template');
					

					//var contoh=document.createElement('p');
					 //for(var i=0;i<data.nama.nama.length;i++)
					 //var a=container[0].querySelectorAll('div');
					 var a=this.parentElement;
					 var pe=a.querySelector('p');

					// a.appendChild(contoh);
					//var b=a.querySelectorAll('p');

					  var c=pe.textContent;
					  var d=`data.produk.${c}`;
					  var e=eval(d);
					  
					  
					//  var oke=`data.produk.${c}`;

					  // var final=(oke);
					   for(var i=0;i<e.length;i++)
					   {
						var varian=document.createElement('div');
						 //gambar
					   	 var link=`data.gambar.${e[i]}`;//mengambil nama varian digunakan untuk mencari key link di JSON
					   	 var link1=eval(link);
					   	 var pic=document.createElement('img');
					   	 pic.src=link1;
					   	 varian.appendChild(pic);



					   
					   	var p1=document.createElement('p');
					   	var varian1=document.createTextNode(e[i]);
					   	p1.appendChild(varian1);
					   	varian.appendChild(p1);//untuk nama varian produk
					   	//console.log(data.produk[0]);


					   	//deskripsi produk
					   	var varianProduk=e[i];
					   	var desk=`data.deskripsi.${varianProduk}`;
					   var deskripsiProduk=	eval(desk);
					   	 var pDeskrip=document.createElement('p');
					   	 var desk1=document.createTextNode(deskripsiProduk);
					   	 pDeskrip.appendChild(desk1);
					   	 varian.appendChild(pDeskrip);


					   


					   	template.appendChild(varian);

					   }
					   var minimaze=document.createElement('button');
					   
					   template.appendChild(minimaze);
					   minimaze.onclick=function(){
					   	const hapusTemplate=document.getElementsByClassName('template');
					   	hapusTemplate[0].remove();
					   	window.boolTemplate=false;


					   };
					 // console.log(data.produk[0]);
					document.body.appendChild(template);
					window.boolTemplate=true;
					
					}//tutup if

				}//tutup fungsi more



		}
		else if(request.status!=200)
		{
			alert("error");
		}


};
request.send();

