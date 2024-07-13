
const kategori=document.getElementsByClassName('kategori');

var request=new XMLHttpRequest();

request.open('GET','tipe.json',true);
request.onload=function(){

		if(request.status==200){

			
				function see()
				{
					
					const a=this.parentElement;
					
					const b=a.textContent;
					window.location.href=`${b}.html`;
					// window.jenisProduk=b;
					// const skrip=document.createElement('script');
					// skrip.src='penghubung.js';
					// document.body.appendChild(skrip);
					// setTimeout(function(){
						
					// },1000);
					 
				}





				const data=JSON.parse(request.responseText);
				for (var i =0;i< data.tipe.length ; i++) {
					const div=document.createElement('div');
					const img=document.createElement('img');
					img.src=data.linkGambar[i];
					const button=document.createElement('button');
				
					var text=document.createTextNode(data.tipe[i]);//tipe produk
					div.appendChild(img);
					div.appendChild(text);
					div.appendChild(button);

					button.onclick=see;

					//mengirim request tipe produk
					// div.onclick=function(){

					// 	alert(data.tipe[i]);
					// };
					// window.tipeProduk=data.
					
					kategori[0].appendChild(div);

					
				}
			}

		else if(request.status!=200){
			alert("error");
		}
	};
	request.send();

	const h1=document.querySelector('h1');

const modeTombol=document.getElementById('mode');
const social=document.querySelectorAll('#social div');

const body=document.body;
modeTombol.onclick=mode;
function mode(){
	
		if(body.style.backgroundColor=='black'){
			//mode siang
			h1.style.color='black';
			document.querySelectorAll('.kategori div').forEach(teks=>{
				teks.style.color='black';
			});
			modeTombol.style.backgroundImage="url('icon/day.png')";
			body.style.backgroundImage="url('icon/backgroundTinggi2.jpg')";
			body.style.backgroundColor='transparent';
		}
		else if(body.style.backgroundImage!='none'){
			h1.style.color='lightblue';
			document.querySelectorAll('.kategori div').forEach(teks=>{
				teks.style.color='lightblue';
			});
			body.style.backgroundColor='black';// mode malam
			modeTombol.style.backgroundImage="url('icon/night.png')";
			body.style.backgroundImage='none';
		}


}
const daftarMedia=['facebook','wa','tiktok','ig'];

for (var i =0;i< social.length;i++) {




				if(i==0){
							social[i].onclick=function (){
								alert("ini facebook");
							};

						}
					if(i==1){
								social[i].onclick=function (){
									alert("ini wa");
								};
							}

					if(i==2){
								social[i].onclick=function (){
									alert("ini tiktok");
								};
							}

					if(i==3){
								social[i].onclick=function (){
									alert("ini ig");
								};
							}

				
}
