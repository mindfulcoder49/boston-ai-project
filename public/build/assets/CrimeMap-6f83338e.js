import{o as p,f,b as t,t as m,r as F,i as g,k as h,F as x,l as P,a as w,Q as R,m as v,p as Q,q as k,s as q,g as j,x as $,v as G,y as A,z as V,c as Y,w as M,u as z,Z as W}from"./app-f022cefe.js";import{l as D,P as H}from"./PageTemplate-761bbc60.js";import"./leaflet.markercluster-src-38600aa4.js";import{_ as S}from"./_plugin-vue_export-helper-c27b6911.js";import"./GuestLayout-d46d9dff.js";import"./ResponsiveNavLink-5c6d3389.js";import"./AuthenticatedLayout-bc793430.js";const K={name:"CrimeData",props:{crimeData:{type:Object,required:!0}},methods:{formatDate(r){const{month:u,hour:n,year:_}=r;return`${u}/${_} ${n}:00`}}},Z={class:"crime-card p-4 border rounded-md shadow-md mb-4 bg-white hover:bg-gray-100 transition duration-300 ease-in-out"},J={class:"flex flex-col md:flex-row md:justify-between md:items-center"},X={class:"mb-2 md:mb-0"},ee={class:"text-xl font-semibold mb-1"},te={class:"text-gray-600"},ae={class:"text-sm text-gray-500"},oe={class:"mt-2 text-sm text-gray-600"};function se(r,u,n,_,b,d){return p(),f("div",Z,[t("div",J,[t("div",X,[t("h4",ee,"Incident Number: "+m(n.crimeData.incident_number),1),t("p",te,"Offense: "+m(n.crimeData.offense_description),1)]),t("div",ae,[t("p",null,"District: "+m(n.crimeData.district),1),t("p",null,"Date: "+m(d.formatDate(n.crimeData)),1)])]),t("div",oe,[t("p",null,"Offense Code: "+m(n.crimeData.offense_code),1),t("p",null,"Reporting Area: "+m(n.crimeData.reporting_area),1),t("p",null,"Shooting: "+m(n.crimeData.shooting?"Yes":"No"),1),t("p",null,"Street: "+m(n.crimeData.street),1),t("p",null,"Location: "+m(n.crimeData.location),1),t("p",null,"Offense Category: "+m(n.crimeData.offense_category),1)])])}const le=S(K,[["render",se],["__scopeId","data-v-b5041b7c"]]),ne={name:"CrimeDataList",components:{CrimeData:le},props:{filteredCrimeData:{type:Array,required:!0},itemsPerPage:{type:Number,default:10}},data(){return{currentPage:1,inputPage:1}},computed:{totalPages(){return Math.ceil(this.filteredCrimeData.length/this.itemsPerPage)},paginatedCrimeData(){const r=(this.currentPage-1)*this.itemsPerPage,u=r+this.itemsPerPage;return this.filteredCrimeData.slice(r,u)}},watch:{currentPage(r){this.inputPage=r}},methods:{nextPage(){this.currentPage<this.totalPages&&this.currentPage++},prevPage(){this.currentPage>1&&this.currentPage--},goToPage(){this.inputPage>=1&&this.inputPage<=this.totalPages?this.currentPage=this.inputPage:this.inputPage=this.currentPage}}},re={class:"flex justify-between items-center mt-4"},ie=["disabled"],de=t("span",null,"Page ",-1),ue=["max"],ce=["disabled"],me={class:"pl-5"},pe={key:0,class:"text-gray-500"},fe={key:1,class:"text-gray-500"};function ge(r,u,n,_,b,d){const o=F("CrimeData");return p(),f("div",null,[t("div",re,[t("button",{onClick:u[0]||(u[0]=(...c)=>d.prevPage&&d.prevPage(...c)),disabled:b.currentPage===1,class:"p-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"},"Previous",8,ie),t("div",null,[de,g(t("input",{"onUpdate:modelValue":u[1]||(u[1]=c=>b.inputPage=c),onChange:u[2]||(u[2]=(...c)=>d.goToPage&&d.goToPage(...c)),type:"number",min:"1",max:d.totalPages,class:"w-16 p-1 border rounded-md text-center"},null,40,ue),[[h,b.inputPage,void 0,{number:!0}]]),t("span",null," of "+m(d.totalPages),1)]),t("button",{onClick:u[3]||(u[3]=(...c)=>d.nextPage&&d.nextPage(...c)),disabled:b.currentPage===d.totalPages,class:"p-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"},"Next",8,ce)]),t("ul",me,[d.paginatedCrimeData.length===0?(p(),f("li",pe,"No results found")):(p(),f("li",fe,"Number of results: "+m(n.filteredCrimeData.length),1)),(p(!0),f(x,null,P(d.paginatedCrimeData,(c,O)=>(p(),f("li",{key:O},[w(o,{crimeData:c},null,8,["crimeData"])]))),128))])])}const _e=S(ne,[["render",ge]]);const i=r=>(A("data-v-3b49a29a"),r=r(),V(),r),be={class:""},ve=i(()=>t("h3",{class:"text-2xl font-semibold mb-4"},"Interactive Boston Crime Map",-1)),he=i(()=>t("div",{id:"map",class:"h-[70vh] mb-6"},null,-1)),ye=i(()=>t("h4",{class:"text-lg font-semibold mb-4"},"Natural Language Query",-1)),Ce=i(()=>t("p",{class:"mb-4"},"Enter a natural language query to filter the crime data:",-1)),xe={class:"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"},De={key:0,class:"p-2 border rounded-md w-full mb-4 overflow-scroll",rows:"5",readonly:""},Pe=i(()=>t("h4",{class:"text-lg font-semibold mb-4"},"Or Use Manual Filters",-1)),we=i(()=>t("p",{class:"mb-4"},"Use the manual filters below to filter the crime data:",-1)),Se={class:"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"},Oe={class:"flex flex-col"},ke=i(()=>t("label",{for:"offenseCategory",class:"font-medium mb-1"},"Choose Offense Categories:",-1)),$e=i(()=>t("option",{value:""},"All",-1)),Le=["value"],Me={class:"flex flex-col"},Ae=i(()=>t("label",{for:"district",class:"font-medium mb-1"},"Choose Districts:",-1)),Ve=i(()=>t("option",{value:""},"All",-1)),Ee=["value"],Ie={class:"flex flex-col"},Ne=i(()=>t("label",{for:"year",class:"font-medium mb-1"},"Choose Years:",-1)),Te=i(()=>t("option",{value:""},"All",-1)),Ue=["value"],Be={class:"flex flex-col"},Fe=i(()=>t("label",{for:"offenseCodes",class:"font-medium mb-1"},"Enter Offense Codes (comma separated):",-1)),Re={class:"flex flex-col"},Qe=i(()=>t("label",{for:"startDate",class:"font-medium mb-1"},"Start Date:",-1)),qe={class:"flex flex-col"},je=i(()=>t("label",{for:"endDate",class:"font-medium mb-1"},"End Date:",-1)),Ge={class:"flex flex-col"},Ye=i(()=>t("label",{for:"shooting",class:"font-medium mb-1"},"Shooting:",-1)),ze={class:"flex flex-col"},We=i(()=>t("label",{for:"limit",class:"font-medium mb-1"},"Record Limit:",-1)),He={class:"mt-6"},Ke=i(()=>t("h4",{class:"text-lg font-semibold mb-4"},"List of Crime Data Points",-1)),Ze={__name:"CrimeMapComponent",setup(r){const{props:u}=R(),n=v(null),_=v(null),b=v(u.crimeData||[]),d=v([]),o=v({offense_codes:"",offense_category:[],district:[],start_date:"",end_date:"",year:[],limit:1500,shooting:!1}),c=v("");axios.defaults.headers.common["X-CSRF-TOKEN"]=document.querySelector('meta[name="csrf-token"]').getAttribute("content");const O=v([{value:"murder_and_manslaughter",label:"Murder and Manslaughter"},{value:"rape",label:"Rape"},{value:"robbery",label:"Robbery"},{value:"assault",label:"Assault"},{value:"burglary",label:"Burglary"},{value:"larceny",label:"Larceny"},{value:"auto_theft",label:"Auto Theft"},{value:"simple_assault",label:"Simple Assault"},{value:"arson",label:"Arson"},{value:"forgery_counterfeiting",label:"Forgery and Counterfeiting"},{value:"fraud",label:"Fraud"},{value:"embezzlement",label:"Embezzlement"},{value:"stolen_property",label:"Stolen Property"},{value:"vandalism",label:"Vandalism"},{value:"weapons_violations",label:"Weapons Violations"},{value:"prostitution",label:"Prostitution"},{value:"sex_offenses",label:"Sex Offenses"},{value:"drug_violations",label:"Drug Violations"},{value:"gambling",label:"Gambling"},{value:"child_offenses",label:"Child Offenses"},{value:"alcohol_violations",label:"Alcohol Violations"},{value:"disorderly_conduct",label:"Disorderly Conduct"},{value:"kidnapping",label:"Kidnapping"},{value:"miscellaneous_offenses",label:"Miscellaneous Offenses"},{value:"vehicle_laws",label:"Vehicle Laws"},{value:"investigations",label:"Investigations"},{value:"other_services",label:"Other Services"},{value:"property",label:"Property"},{value:"disputes",label:"Disputes"},{value:"animal_incidents",label:"Animal Incidents"},{value:"missing_persons",label:"Missing Persons"},{value:"other_reports",label:"Other Reports"},{value:"accidents",label:"Accidents"}]),E=["A1","A15","A7","B2","B3","C11","C6","D14","D4","E13"],I=["2024","2023","2022","2021","2020","2019","2018","2017"];Q(()=>{n.value=k(D.map("map").setView([42.3601,-71.0589],13)),D.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png",{maxZoom:19,attribution:'&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'}).addTo(n.value),_.value=k(D.markerClusterGroup()),n.value.addLayer(_.value),y()});const y=async()=>{try{const a=(await axios.post("/api/crime-data",{filters:o.value})).data;_.value.clearLayers(),d.value=a.crimeData,a.crimeData.forEach(e=>{if(e.lat&&e.long){const C=`
          <div>
            <strong>Incident Number:</strong> ${e.incident_number}<br>
            <strong>Offense Code:</strong> ${e.offense_code}<br>
            <strong>Offense Code Group:</strong> ${e.offense_code_group}<br>
            <strong>Offense Description:</strong> ${e.offense_description}<br>
            <strong>District:</strong> ${e.district}<br>
            <strong>Reporting Area:</strong> ${e.reporting_area}<br>
            <strong>Shooting:</strong> ${e.shooting?"Yes":"No"}<br>
            <strong>Occurred On:</strong> ${new Date(e.occurred_on_date).toLocaleString()}<br>
            <strong>Year:</strong> ${e.year}<br>
            <strong>Month:</strong> ${e.month}<br>
            <strong>Day of Week:</strong> ${e.day_of_week}<br>
            <strong>Hour:</strong> ${e.hour}<br>
            <strong>UCR Part:</strong> ${e.ucr_part}<br>
            <strong>Street:</strong> ${e.street}<br>
            <strong>Location:</strong> ${e.location}<br>
            <strong>Offense Category:</strong> ${e.offense_category}
          </div>
        `,l=k(D.marker([e.lat,e.long]));l.bindPopup(C),_.value.addLayer(l)}})}catch(s){console.error("Failed to fetch crime data",s)}},L=s=>typeof s=="string"&&(s=s.replace(/[\r\n]+/g," "),s.includes(","))?`"${s.replace(/"/g,'""')}"`:s,N=()=>{const s=[["Incident Number","Offense Code","Offense Code Group","Offense Description","District","Reporting Area","Shooting","Occurred On Date","Year","Month","Day of Week","Hour","UCR Part","Street","Lat","Long","Location","Offense Category"].map(L).join(","),...d.value.map(l=>[l.incident_number,l.offense_code,l.offense_code_group,l.offense_description,l.district,l.reporting_area,l.shooting?"Yes":"No",new Date(l.occurred_on_date).toLocaleString(),l.year,l.month,l.day_of_week,l.hour,l.ucr_part,l.street,l.lat,l.long,l.location,l.offense_category].map(L).join(","))].join(`
`),a=new Blob([s],{type:"text/csv;charset=utf-8;"}),e=document.createElement("a"),C=URL.createObjectURL(a);e.setAttribute("href",C),e.setAttribute("download","crime_data.csv"),e.style.visibility="hidden",document.body.appendChild(e),e.click(),document.body.removeChild(e)},T=s=>{const a=new Date(s),e=a.getFullYear(),C=String(a.getMonth()+1).padStart(2,"0"),l=String(a.getDate()).padStart(2,"0");return`${e}-${C}-${l}`},U=async()=>{try{document.getElementById("submitQuery").innerText="Loading...",document.getElementById("submitQuery").disabled=!0;const a=(await axios.post("/api/natural-language-query",{query:c.value})).data;b.value=a.crimeData,Object.keys(o.value).forEach(e=>{a.filters.hasOwnProperty(e)?e==="start_date"||e==="end_date"?o.value[e]=T(a.filters[e]):o.value[e]=a.filters[e]:o.value[e]=""}),document.getElementById("submitQuery").innerText="Submit to GPT-4o-mini",document.getElementById("submitQuery").disabled=!1,y()}catch(s){document.getElementById("submitQuery").innerText="Submit to GPT-4o-mini",document.getElementById("submitQuery").disabled=!1,console.error("Failed to process natural language query",s)}},B=()=>{Object.keys(o.value).forEach(s=>{s==="offense_category"||s==="district"||s==="year"?o.value[s]=[]:s==="shooting"?o.value[s]=!1:o.value[s]=""}),y()};return q(o,y,{deep:!0}),(s,a)=>(p(),f(x,null,[t("div",be,[ve,he,ye,Ce,t("div",xe,[t("button",{onClick:a[0]||(a[0]=e=>c.value="All the fraud that happened last week"),class:"p-2 border rounded-md w-full mb-4"},"All the fraud that happened last week"),t("button",{onClick:a[1]||(a[1]=e=>c.value="Last month's welfare checks"),class:"p-2 border rounded-md w-full mb-4"},"Last month's welfare checks"),t("button",{onClick:a[2]||(a[2]=e=>c.value="Todo el robo que ocurrió el mes pasado"),class:"p-2 border rounded-md w-full mb-4"},"Todo el robo que ocurrió el mes pasado")]),g(t("input",{"onUpdate:modelValue":a[3]||(a[3]=e=>c.value=e),type:"text",placeholder:"Example: All the fraud that happened last week",class:"p-2 border rounded-md w-full mb-4"},null,512),[[h,c.value]]),t("button",{onClick:U,id:"submitQuery",class:"p-2 bg-blue-500 text-white rounded-md mb-4"},"Submit to GPT-4o-mini"),o.value?(p(),f("pre",De,m(JSON.stringify(o.value,null,2)),1)):j("",!0),Pe,we,t("div",Se,[t("div",Oe,[ke,g(t("select",{"onUpdate:modelValue":a[4]||(a[4]=e=>o.value.offense_category=e),multiple:"",class:"p-2 border rounded-md"},[$e,(p(!0),f(x,null,P(O.value,e=>(p(),f("option",{key:e.value,value:e.value},m(e.label),9,Le))),128))],512),[[$,o.value.offense_category]])]),t("div",Me,[Ae,g(t("select",{"onUpdate:modelValue":a[5]||(a[5]=e=>o.value.district=e),multiple:"",class:"p-2 border rounded-md"},[Ve,(p(),f(x,null,P(E,e=>t("option",{key:e,value:e},m(e),9,Ee)),64))],512),[[$,o.value.district]])]),t("div",Ie,[Ne,g(t("select",{"onUpdate:modelValue":a[6]||(a[6]=e=>o.value.year=e),multiple:"",class:"p-2 border rounded-md"},[Te,(p(),f(x,null,P(I,e=>t("option",{key:e,value:e},m(e),9,Ue)),64))],512),[[$,o.value.year]])]),t("div",Be,[Fe,g(t("input",{"onUpdate:modelValue":a[7]||(a[7]=e=>o.value.offense_codes=e),class:"p-2 border rounded-md",placeholder:"1103, 1104"},null,512),[[h,o.value.offense_codes]])]),t("div",Re,[Qe,g(t("input",{type:"date","onUpdate:modelValue":a[8]||(a[8]=e=>o.value.start_date=e),class:"p-2 border rounded-md"},null,512),[[h,o.value.start_date]])]),t("div",qe,[je,g(t("input",{type:"date","onUpdate:modelValue":a[9]||(a[9]=e=>o.value.end_date=e),class:"p-2 border rounded-md"},null,512),[[h,o.value.end_date]])]),t("div",Ge,[Ye,g(t("input",{type:"checkbox","onUpdate:modelValue":a[10]||(a[10]=e=>o.value.shooting=e),class:"p-2 border rounded-md"},null,512),[[G,o.value.shooting]])]),t("div",ze,[We,g(t("input",{type:"number","onUpdate:modelValue":a[11]||(a[11]=e=>o.value.limit=e),class:"p-2 border rounded-md"},null,512),[[h,o.value.limit]])])]),t("button",{onClick:y,class:"mt-4 p-2 bg-blue-500 text-white rounded-md"},"Submit Filters"),t("button",{onClick:B,class:"m-4 p-2 bg-blue-500 text-white rounded-md"},"Clear Filters")]),t("div",He,[Ke,t("button",{onClick:N,class:"p-2 bg-green-500 text-white rounded-md mb-4"},"Download as CSV"),w(_e,{filteredCrimeData:d.value},null,8,["filteredCrimeData"])])],64))}},Je=S(Ze,[["__scopeId","data-v-3b49a29a"]]);const Xe=r=>(A("data-v-37f407ee"),r=r(),V(),r),et=Xe(()=>t("title",null,"Crime Map",-1)),tt={__name:"CrimeMap",setup(r){return(u,n)=>(p(),Y(H,null,{default:M(()=>[w(z(W),null,{default:M(()=>[et]),_:1}),w(Je)]),_:1}))}},dt=S(tt,[["__scopeId","data-v-37f407ee"]]);export{dt as default};