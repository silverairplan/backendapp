<?php 
	namespace App\Http\Controllers;


	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\App;

	class Article extends Controller
	{
		public function get_article(Request $request)
		{
			$date = $request->input('date');
			
		}
	}

?>