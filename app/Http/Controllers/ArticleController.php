<?php 
	namespace App\Http\Controllers;


	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\App;
	use App\Model\Article;

	class ArticleController extends Controller
	{
		public function get_article(Request $request)
		{
			$articles = Article::orderBy('created_at','DESC')->get();
			return array('success'=>true,'articles'=>$articles);
		}

		public function init_article(Request $request)
		{
			$articles = [
				['title'=>'Article','content'=>'This page must contain a referral link so friends may share the app for 5 extra limit order alerts AND must contain a referral link so a user may send their profile so friends can follow'],
				['title'=>'Update','content'=>'This page must contain a referral link so friends may share the app for 5 extra limit order alerts AND must contain a referral link so a user may send their profile so friends can follow'],
				['title'=>'Article','content'=>'This page must contain a referral link so friends may share the app for 5 extra limit order alerts AND must contain a referral link so a user may send their profile so friends can follow']
			];

			foreach ($articles as $article) {
				Article::create($article);
			}

			return array('success'=>false);
		}
	}

?>